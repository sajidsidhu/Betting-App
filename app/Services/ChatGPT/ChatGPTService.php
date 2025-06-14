<?php

namespace App\Services\ChatGPT;

use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        "language": "detected language code",
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
                'model' => 'gpt-4.1-mini',
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
                return $this->createErrorResponse('Failed to process ticket');
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
            $errors = $this->validateTicketData($data);
            if ($errors) {
                Log::error('Invalid ticket data structure', ['data' => $data]);
                return $this->createErrorResponse('Invalid ticket data', $errors);
            }

            return $data;

        } catch (\Throwable $e) {
            Log::error('Error processing ticket image', [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->createErrorResponse('Failed to process ticket');
        }
    }

    private function createErrorResponse(string $message, $errors = []): array
    {
        return [
            'is_readable' => false,
            'error_message' => $message,
            'errors' => $errors,
            'summary' => [
                'language' => 'unknown',
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

    private function validateTicketData(array $data): bool|array
    {
        $validator = Validator::make($data, [
            'is_readable' => 'required|boolean',
            'error_message' => 'nullable|string',
            'source_language' => 'required|string',
            'summary' => 'required|array',
            'summary.ticket_date' => 'nullable|date',
            'summary.bookmaker' => 'nullable|string',
            'summary.total_odd' => 'nullable|numeric',
            'summary.total_price' => 'nullable|numeric',
            'summary.potential_win' => 'nullable|numeric',
            'summary.status' => 'nullable|string',
            'bets' => 'required|array',
            'bets.*.game_datetime' => 'nullable|date',
            'bets.*.team_1' => 'required|string',
            'bets.*.team_2' => 'required|string',
            'bets.*.bet_type' => 'required|string',
            'bets.*.odds' => 'required|numeric',
            'bets.*.prediction' => 'required|string',
            'bets.*.game_type' => 'required|string',
            'bets.*.raw_text' => 'required|string',
            'bets.*.status' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return false; // No validation errors
    }
}
