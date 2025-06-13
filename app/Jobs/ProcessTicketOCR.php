<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\ChatGPT\ChatGPTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTicketOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Ticket $ticket
    ) {}

    public function handle(ChatGPTService $chatGPTService): void
    {
        try {
            // Extract text from image using ChatGPT Vision API
            $ocrResult = $chatGPTService->extractTextFromImage($this->ticket->image_path);

            // Parse the OCR result using ChatGPT
            $structuredData = $chatGPTService->parseTicketData($ocrResult);

            // Update ticket with summary data
            $this->ticket->update([
                'source_language' => $structuredData['language'] ?? null,
                'total_odd' => $structuredData['total_odd'] ?? null,
                'total_price' => $structuredData['total_price'] ?? null,
                'potential_win' => $structuredData['potential_win'] ?? null
            ]);

            // Create bets from structured data
            foreach ($structuredData['bets'] as $betData) {
                $this->ticket->bets()->create([
                    'date_time' => $betData['date_time'],
                    'raw_text' => $betData['raw_text'],
                    'parsed_data' => $betData['parsed_data']
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Failed to process ticket OCR', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
