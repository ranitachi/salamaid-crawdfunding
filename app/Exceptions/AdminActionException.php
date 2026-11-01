<?php

namespace App\Exceptions;

use RuntimeException;

class AdminActionException extends RuntimeException
{
    public static function invalidTransition(string $entity, string $from, string $to): self
    {
        return new self(sprintf(
            'Transisi status %s dari "%s" ke "%s" tidak diizinkan.',
            $entity,
            $from,
            $to,
        ));
    }

    public static function requirement(string $message): self
    {
        return new self($message);
    }
}
