<?php

namespace App\Console\Commands;

use App\Services\Games\GamePayments;
use Illuminate\Console\Command;

/**
 * The web checkout's round (Games > Website > Payments), every minute: reads
 * the receipts a request didn't finish checking, tells the game server about
 * payments it hasn't granted yet, and lets unpaid links run out.
 *
 *   php artisan games:payments
 */
class GamesPaymentsCommand extends Command
{
    protected $signature = 'games:payments';

    protected $description = 'Check web checkout receipts, deliver approved payments to the game, expire unpaid links';

    public function handle(GamePayments $payments): int
    {
        $done = $payments->sweep();
        $this->info("Checked {$done['checked']}, delivered {$done['fulfilled']}, expired {$done['expired']}.");

        return self::SUCCESS;
    }
}
