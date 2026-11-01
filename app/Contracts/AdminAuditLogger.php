<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface AdminAuditLogger
{
    /**
     * Catat perubahan status atau aksi moderasi sensitif.
     *
     * Implementasi database akan dibuat pada fase A11. Implementasi saat ini
     * menulis ke log aplikasi agar titik integrasi tidak hilang.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, mixed> $context
     */
    public function log(
        string $action,
        Model $subject,
        array $before,
        array $after,
        ?string $reason = null,
        ?User $actor = null,
        array $context = [],
    ): void;
}
