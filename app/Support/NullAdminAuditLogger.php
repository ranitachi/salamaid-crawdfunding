<?php

namespace App\Support;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NullAdminAuditLogger implements AdminAuditLogger
{
    public function log(
        string $action,
        Model $subject,
        array $before,
        array $after,
        ?string $reason = null,
        ?User $actor = null,
        array $context = [],
    ): void {
        // Sengaja tidak melakukan apa pun. Dipakai pada test yang tidak
        // membutuhkan pencatatan audit.
    }
}
