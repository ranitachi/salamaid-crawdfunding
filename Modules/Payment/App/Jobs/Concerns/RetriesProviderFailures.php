<?php

namespace Modules\Payment\App\Jobs\Concerns;

use Modules\Payment\App\Exceptions\MayarRequestException;

/**
 * Shared retry policy for provider calls: 5 attempts with increasing backoff.
 * Non-retryable failures (4xx, bad config) fail immediately; 429 honours Retry-After.
 */
trait RetriesProviderFailures
{
    public int $tries = 5;

    /** Seconds; the HTTP client itself times out after config('payment.mayar.timeout'). */
    public int $timeout = 30;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    protected function handleProviderFailure(MayarRequestException $exception): void
    {
        if (! $exception->retryable) {
            $this->fail($exception);

            return;
        }

        if ($exception->retryAfter && $this->job && $this->job->getConnectionName() !== 'sync') {
            $this->release($exception->retryAfter);

            return;
        }

        throw $exception;
    }
}
