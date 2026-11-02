<?php

namespace Modules\Payment\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Jobs\Concerns\RetriesProviderFailures;
use Modules\Payment\App\Services\ProcessWebhookAction;
use Modules\Payment\Entities\Webhook;
use Throwable;

/** Processes one recorded webhook; unique per webhook and idempotent via processed_at. */
class ProcessPaymentWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, RetriesProviderFailures;

    public int $uniqueFor = 3600;

    public function __construct(public readonly string $webhookId)
    {
    }

    public function uniqueId(): string
    {
        return $this->webhookId;
    }

    public function handle(ProcessWebhookAction $action): void
    {
        $webhook = Webhook::query()->find($this->webhookId);

        if (! $webhook || $webhook->processed_at) {
            return;
        }

        try {
            $action->process($webhook, $this->attempts());
        } catch (MayarRequestException $exception) {
            $this->handleProviderFailure($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Webhook::query()->whereKey($this->webhookId)->whereNull('processed_at')->update([
            'failed_at' => now(),
            'last_error' => mb_substr((string) $exception?->getMessage(), 0, 255) ?: 'Pemrosesan webhook gagal.',
        ]);
    }
}
