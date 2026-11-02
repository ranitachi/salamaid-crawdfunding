<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Payment\App\Jobs\SyncPaymentStatusJob;
use Modules\Payment\Entities\Payment;

class SyncPendingPayments extends Command
{
    protected $signature = 'payment:sync-pending
        {--minutes= : Only sync payments pending for at least this many minutes}
        {--limit= : Maximum number of payments to dispatch per run}';

    protected $description = 'Dispatch provider inquiries for stale pending Mayar payments';

    public function handle(): int
    {
        $minutes = max(1, (int) ($this->option('minutes') ?: config('payment.pending_sync.minutes', 15)));
        $limit = max(1, (int) ($this->option('limit') ?: config('payment.pending_sync.limit', 100)));
        $cutoff = now()->subMinutes($minutes);
        $dispatched = 0;

        Payment::query()
            ->where('provider', 'mayar')
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->oldest('created_at')
            ->limit($limit)
            ->cursor()
            ->each(function (Payment $payment) use (&$dispatched): void {
                SyncPaymentStatusJob::dispatch((string) $payment->getKey(), 'scheduled');
                $dispatched++;
            });

        $this->info("Dispatched {$dispatched} pending payment sync job(s).");

        return self::SUCCESS;
    }
}
