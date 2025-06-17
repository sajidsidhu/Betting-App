<?php

namespace App\Console\Commands;

use App\Jobs\CheckPendingBets;
use Illuminate\Console\Command;

class VerifyPendingBets extends Command
{
    protected $signature = 'bets:verify';
    protected $description = 'Verify pending bets results';

    public function handle(): void
    {
        $this->info('Dispatching bet verification job...');
        CheckPendingBets::dispatch();
        $this->info('Job dispatched successfully.');
    }
}
