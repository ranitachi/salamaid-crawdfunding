<?php

namespace Modules\Payment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Webhook extends Model
{
    protected $table = 'webhooks';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'provider', 'event', 'order_id', 'signature', 'payload', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $webhook) => $webhook->id ??= (string) Str::uuid());
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'order_id', 'order_id');
    }
}
