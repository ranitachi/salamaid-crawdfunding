<?php

namespace App\Support;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LogAdminAuditLogger implements AdminAuditLogger
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
        Log::info('admin.audit', [
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'actor_id' => $actor?->getKey(),
            'actor_email' => $actor?->email,
            'actor_role' => $actor?->role,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'context' => $context,
            'at' => now()->toIso8601String(),
        ]);
    }
}
