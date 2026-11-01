<?php

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignMedia extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['campaign_id','type','url','caption','sort'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function campaign(){ return $this->belongsTo(Campaign::class); }
}
