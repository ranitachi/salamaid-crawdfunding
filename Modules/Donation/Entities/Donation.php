<?php

namespace Modules\Donation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Donation extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = [
        'campaign_id','user_id','gross_amount','fee_platform','fee_channel',
        'net_amount','method','anonymous','message','ref_code','status','paid_at','expired_at'
    ];
    protected $casts = ['anonymous'=>'boolean','paid_at'=>'datetime','expired_at'=>'datetime'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function campaign(){ return $this->belongsTo(\Modules\Catalog\Entities\Campaign::class); }
    public function user(){ return $this->belongsTo(\App\Models\User::class); }
    public function payment(){ return $this->hasOne(\Modules\Payment\Entities\Payment::class); }
}
