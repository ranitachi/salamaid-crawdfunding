<?php

namespace Modules\Payment\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = [
        'donation_id','provider','order_id','channel_code','va_number','qris_payload',
        'redirect_url','status','expired_at','paid_at','signature','raw_request_json','raw_callback_json'
    ];
    protected $casts = ['raw_request_json'=>'array','raw_callback_json'=>'array','expired_at'=>'datetime','paid_at'=>'datetime'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function donation(){ return $this->belongsTo(\Modules\Donation\Entities\Donation::class); }

    public function webhooks(){ return $this->hasMany(Webhook::class, 'order_id', 'order_id'); }
}
