<?php

namespace Modules\Payment\App\Exceptions;

use RuntimeException;

/**
 * Provider call failure with a reduced, credential-free message.
 * `retryable` marks transient failures (timeout, 5xx, 429) for queue retry.
 */
class MayarRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly bool $retryable = false,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }
}
