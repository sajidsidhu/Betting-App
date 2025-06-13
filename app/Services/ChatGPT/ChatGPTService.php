<?php

namespace App\Services\ChatGPT;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ChatGPTService
{
    private string $apiKey;
    private string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    private string $visionEndpoint = 'https://api.openai.com/v1/chat/completions';

    private const SYSTEM_PROMPT = <<<'EOT'
You are a betting ticket analyzer. Your task is to extract information from betting ticket images and return it in a specific JSON format.
Required format:
{
    "is_readable": true/false,
    "error_message": null or error description if not readable,
    "source_language": "detected language code",
    "summary": {
        "total_odd": numeric value,
        "total_price": numeric value,
        "potential_win": numeric value,
        "ticket_date": "YYYY-MM-DD HH:mm:ss",
        "bookmaker": "name if detected",
        "status": "pending/partial_won/all_won/lost"
    },
    "bets": [
        {
            "game_datetime": "YYYY-MM-DD HH:mm:ss",
            "team_1": "team name",
            "team_2": "team name",
            "bet_type": "1X2/Over-Under/etc",
            "odds": numeric value,
            "prediction": "user's prediction",
            "game_type": "soccer/basketball/etc",
            "raw_text": "original text from ticket",
            "status": "pending/won/lost"
        }
    ]
}
Ensure all dates are in UTC format.
If the image is unclear or text is unreadable, set is_readable to false and provide a detailed error message.
check the all bets results and set the status of the ticket based on the bets results, pending if match results not yet available, won or lost for each bet.
set the over all summary status all_won if all bets won, partial_won if some bets won and some pending, lost if any of all bets lost.
EOT;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured');
        }
    }

    public function extractTextFromImage(string $imagePath): array
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                return $this->createErrorResponse('Image file not found');
            }

            $imageData = base64_encode(Storage::disk('public')->get($imagePath));
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->visionEndpoint, [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyze this betting ticket and provide all information in the specified JSON format. If the image is unclear or text is not readable, set is_readable to false with a descriptive error message.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/jpeg;base64,{$imageData}"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ]);

            if (!$response->successful()) {
                Log::error('ChatGPT API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return $this->createErrorResponse('Failed to process image with ChatGPT API');
            }

            $content = $response->json('choices.0.message.content');
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse ChatGPT response as JSON', [
                    'content' => $content,
                    'error' => json_last_error_msg()
                ]);
                return $this->createErrorResponse('Failed to parse ticket data');
            }

            if (!$this->validateTicketData($data)) {
                Log::error('Invalid ticket data structure', ['data' => $data]);
                return $this->createErrorResponse('Invalid ticket data structure');
            }

            return $data;

        } catch (\Throwable $e) {
            Log::error('Error processing ticket image', [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->createErrorResponse('Failed to process ticket: ' . $e->getMessage());
        }
    }

    private function createErrorResponse(string $message): array
    {
        return [
            'is_readable' => false,
            'error_message' => $message,
            'source_language' => null,
            'summary' => [
                'total_odd' => 0,
                'total_price' => 0,
                'potential_win' => 0,
                'ticket_date' => null,
                'bookmaker' => null,
                'status' => 'pending'
            ],
            'bets' => []
        ];
    }

    private function validateTicketData(array $data): bool
    {
        if (!isset($data['is_readable']) || !is_bool($data['is_readable'])) {
            return false;
        }

        if ($data['is_readable'] === false) {
            return isset($data['error_message']) && is_string($data['error_message']);
        }

        if (!isset($data['summary']) || !is_array($data['summary'])) {
            return false;
        }

        $requiredSummaryFields = [
            'total_odd' => 'numeric',
            'total_price' => 'numeric',
            'potential_win' => 'numeric',
            'ticket_date' => 'string',
            'status' => 'string',
        ];

        foreach ($requiredSummaryFields as $field => $type) {
            if (!isset($data['summary'][$field])) {
                return false;
            }
            if ($type === 'numeric' && !is_numeric($data['summary'][$field])) {
                return false;
            }
            if ($type === 'string' && !is_string($data['summary'][$field])) {
                return false;
            }
        }

        if (!isset($data['bets']) || !is_array($data['bets']) || empty($data['bets'])) {
            return false;
        }

        foreach ($data['bets'] as $bet) {
            $requiredBetFields = [
                'game_datetime' => 'string',
                'team_1' => 'string',
                'team_2' => 'string',
                'bet_type' => 'string',
                'odds' => 'numeric',
                'prediction' => 'string',
                'game_type' => 'string',
                'raw_text' => 'string',
                'status' => 'string',
            ];

            foreach ($requiredBetFields as $field => $type) {
                if (!isset($bet[$field])) {
                    return false;
                }
                if ($type === 'numeric' && !is_numeric($bet[$field])) {
                    return false;
                }
                if ($type === 'string' && !is_string($bet[$field])) {
                    return false;
                }
            }
        }

        return true;
    }
}
