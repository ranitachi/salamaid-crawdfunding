<?php

namespace Modules\Payment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Append-only record of one provider status sync attempt. */
class PaymentSyncLog extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['*'];
    protected $casts = ['response_summary' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $log) => $log->id ??= (string) Str::uuid());
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
