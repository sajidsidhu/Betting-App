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
use Illuminate\Support\Facades\Validator;

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

            // Validate OCR result structure
            // $this->validateOCRResult($ocrResult);

            // Update ticket with extracted data
            $this->updateTicket($ocrResult);

            // If ticket is readable, create bets
            if ($ocrResult['is_readable']) {
                $this->createBets($ocrResult['bets']);
            }

            Log::info('Ticket processed successfully', [
                'ticket_id' => $this->ticket->id,
                'is_readable' => $ocrResult['is_readable'],
                'status' => $ocrResult['summary']['status'] ?? 'pending'
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to process ticket OCR', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update ticket with error
            $this->ticket->update([
                'is_readable' => false,
                'error_message' => $e->getMessage(),
                'status' => 'pending',
            ]);
            
            throw $e;
        }
    }


    private function updateTicket(array $data): void
    {
        $summary = $data['summary'];
        
        $updateData = [
            'is_readable' => $data['is_readable'],
            'error_message' => $data['error_message'],
            'errors' => $data['errorS'] ?? null,
            'source_language' => $data['source_language'] ?? 'en',
            'translation_json' => $data,
            'status' => $summary['status'] ?? 'pending'
        ];

        // Only include numeric fields if they are not null to avoid setting them to 0
        foreach (['total_odd', 'total_price', 'potential_win'] as $field) {
            if (isset($summary[$field]) && $summary[$field] !== null) {
                $updateData[$field] = $summary[$field];
            }
        }

        // Add optional fields if they exist
        if (isset($summary['ticket_date'])) {
            $updateData['ticket_date'] = $summary['ticket_date'];
        }
        if (isset($summary['bookmaker'])) {
            $updateData['bookmaker'] = $summary['bookmaker'];
        }

        $this->ticket->update($updateData);
    }

    private function createBets(array $betsData): void
    {
        foreach ($betsData as $betData) {
            $bet = $this->ticket->bets()->create([
                'date_time' => $betData['game_datetime'],
                'team_1' => $betData['team_1'],
                'team_2' => $betData['team_2'],
                'bet_type' => $betData['bet_type'],
                'odds' => $betData['odds'],
                'prediction' => $betData['prediction'],
                'game_type' => $betData['game_type'],
                'raw_text' => $betData['raw_text'],
                'status' => $betData['status'] ?? 'pending',
                'parsed_data' => $betData
            ]);
        }
    }
}
