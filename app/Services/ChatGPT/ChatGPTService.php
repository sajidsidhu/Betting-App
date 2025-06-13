<?php

namespace App\Services\ChatGPT;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ChatGPTService
{
    private string $apiKey;
    private string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    private string $visionEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function extractTextFromImage(string $imagePath): string
    {
        $imageData = base64_encode(Storage::disk('public')->get($imagePath));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->visionEndpoint, [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract all text from this betting ticket image. Focus on odds, dates, teams, and bet types.'
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
            'max_tokens' => 500
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to process image with ChatGPT Vision: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    public function parseTicketData(string $ocrText): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiEndpoint, [
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a betting ticket parser. Extract structured data from the OCR text.'
                ],
                [
                    'role' => 'user',
                    'content' => "Parse this betting ticket text and return a JSON structure with: language, total_odd, total_price, potential_win, and an array of bets. Each bet should have: date_time, raw_text, and parsed_data (including game_type, teams, odds, etc.).\n\n" . $ocrText
                ]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to parse ticket data with ChatGPT: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}
