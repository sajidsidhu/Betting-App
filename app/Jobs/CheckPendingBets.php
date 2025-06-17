<?php

namespace App\Jobs;

use App\Models\Bet;
use App\Services\BetVerificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPendingBets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 1m, 2m
    }

    public function handle(BetVerificationService $verificationService): void
    {
        try {
            // Get pending bets where game time has passed (with 30min buffer)
            $cutoffTime = Carbon::now()->subMinutes(30);
            
            Bet::where('status', 'pending')
                ->where('date_time', '<', $cutoffTime)
                ->whereNull('verified_at')
                ->chunk(10, function ($bets) use ($verificationService) {
                    foreach ($bets as $bet) {
                        try {
                            $verificationService->verifyBet($bet);
                            // Add small delay to respect API rate limits
                            sleep(2);
                        } catch (\Exception $e) {
                            Log::error('Failed to verify bet', [
                                'bet_id' => $bet->id,
                                'error' => $e->getMessage()
                            ]);
                            continue;
                        }
                    }
                });
        } catch (\Exception $e) {
            Log::error('CheckPendingBets job failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckPendingBets job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}
