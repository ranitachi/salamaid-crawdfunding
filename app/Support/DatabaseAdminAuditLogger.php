<?php

namespace App\Support;

use App\Contracts\AdminAuditLogger;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DatabaseAdminAuditLogger implements AdminAuditLogger
{
    public function __construct(private readonly ?Request $request = null)
    {
    }

    public function log(string $action, Model $subject, array $before, array $after, ?string $reason = null, ?User $actor = null, array $context = []): void
    {
        AdminAuditLog::create([
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'actor_id' => $actor?->getKey(),
            'actor_email' => $actor?->email,
            'actor_role' => $actor?->role,
            'reason' => $reason,
            'before' => $this->sanitize($before),
            'after' => $this->sanitize($after),
            'context' => $this->sanitize($context),
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ]);
    }

    private function sanitize(array $values): array
    {
        $blocked = ['password', 'token', 'secret', 'signature', 'raw_request_json', 'raw_callback_json'];

        return collect($values)->mapWithKeys(function ($value, $key) use ($blocked) {
            $key = (string) $key;
            return [$key => in_array(strtolower($key), $blocked, true)
                ? '[REDACTED]'
                : (is_array($value) ? $this->sanitize($value) : $value)];
        })->all();
    }
}
