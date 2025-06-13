<?php

namespace App\Console\Commands;

use App\Models\Bet;
use Illuminate\Console\Command;

class CheckBetResults extends Command
{
    protected $signature = 'bets:check-results';
    protected $description = 'Check results for pending bets';

    public function handle(): void
    {
        $this->info('Starting to check bet results...');

        $pendingBets = Bet::where('status', 'pending')
            ->whereNull('verified_at')
            ->with('ticket')
            ->get();

        foreach ($pendingBets as $bet) {
            $this->info("Checking bet ID: {$bet->id}");

            try {
                // TODO: Implement result checking logic here
                // This could involve calling external APIs or web scraping
                // For now, we'll just log that we processed it
                
                $bet->update([
                    'verified_at' => now()
                ]);

                // Update parent ticket status
                $bet->ticket->updateStatusBasedOnBets();

            } catch (\Exception $e) {
                $this->error("Error processing bet {$bet->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info('Finished checking bet results.');
    }
}
