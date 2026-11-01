<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'action', 'subject_type', 'subject_id', 'actor_id', 'actor_email',
        'actor_role', 'reason', 'before', 'after', 'context', 'ip_address', 'user_agent',
    ];

    protected $casts = ['before' => 'array', 'after' => 'array', 'context' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
