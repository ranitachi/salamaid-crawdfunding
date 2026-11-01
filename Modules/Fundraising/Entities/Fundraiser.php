<?php

namespace Modules\Fundraising\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Fundraiser extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['campaign_id','user_id','ref_code','title','total_collected'];

    protected static function booted() {
        static::creating(function($m){
            $m->id ??= (string) Str::uuid();
            $m->ref_code ??= strtoupper(Str::random(8));
        });
    }

    public function campaign(){ return $this->belongsTo(\Modules\Catalog\Entities\Campaign::class); }
    public function user(){ return $this->belongsTo(\App\Models\User::class); }
}
