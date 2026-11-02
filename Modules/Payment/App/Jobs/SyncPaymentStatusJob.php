<?php

namespace Modules\Payment\App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Jobs\Concerns\RetriesProviderFailures;
use Modules\Payment\App\Services\SyncPaymentStatusAction;
use Modules\Payment\Entities\Payment;

/** Provider inquiry for one payment; unique per payment so repeated clicks do not stack calls. */
class SyncPaymentStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, RetriesProviderFailures;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $trigger = 'manual',
        public readonly ?int $actorId = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->paymentId;
    }

    public function handle(SyncPaymentStatusAction $action): void
    {
        $payment = Payment::query()->with('donation:id,gross_amount')->find($this->paymentId);

        if (! $payment) {
            return;
        }

        $trigger = $this->attempts() > 1 ? 'retry' : $this->trigger;
        $actor = $this->actorId ? User::query()->find($this->actorId) : null;

        try {
            $action->execute($payment, $trigger, $actor, $this->attempts());
        } catch (MayarRequestException $exception) {
            $this->handleProviderFailure($exception);
        }
    }
}
