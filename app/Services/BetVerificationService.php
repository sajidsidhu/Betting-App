<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;
use App\Models\Bet;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BetVerificationService
{
    protected string $apiKey;
    protected string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured');
        }
    }

    public function verifyBet(Bet $bet): bool
    {
        try {
            $prompt = $this->formatBetPrompt($bet);
            $result = $this->queryGPT($prompt);
            
            if (!$result) {
                return false;
            }

            // Update bet with verification results
            $bet->update([
                'status' => $result['status'],
                'result_summary' => $result['summary'],
                'result_source_url' => $result['source'],
                'verified_at' => now()
            ]);

            // Update ticket status if all bets are verified
            $this->updateTicketStatus($bet->ticket);

            return true;
        } catch (\Exception $e) {
            Log::error('Bet verification failed', [
                'bet_id' => $bet->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function formatBetPrompt(Bet $bet): string
    {
        return sprintf(
            "Check the result of this bet:\n\n%s vs %s\nMatch Date: %s\nBet Type: %s\nPrediction: %s\nGame Type: %s\n\nPlease search online and provide:\n- Outcome (Win/Loss/Draw)\n- Whether the bet is won or lost\n- A short summary\n- Source link",
            $bet->team_1,
            $bet->team_2,
            $bet->date_time->format('Y-m-d'),
            $bet->bet_type,
            $bet->prediction,
            $bet->game_type
        );
    }

    protected function queryGPT(string $prompt): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiEndpoint, [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a sports analyst assistant. Your job is to check if a sports bet has won or lost based on real-time web search.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);

        if (!$response->successful()) {
            Log::error('GPT API error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
        }

        $content = $response->json('choices.0.message.content');
        return json_decode($content, true);
    }

    protected function updateTicketStatus(Ticket $ticket): void
    {
        $bets = $ticket->bets()->get();
        $allVerified = $bets->every(fn($bet) => $bet->verified_at !== null);
        
        if ($allVerified) {
            $wonCount = $bets->where('status', 'won')->count();
            $totalBets = $bets->count();
            
            $status = match(true) {
                $wonCount === $totalBets => 'all_won',
                $wonCount > 0 => 'partial_won',
                default => 'lost'
            };
            
            $ticket->update(['status' => $status]);
        }
    }
}
