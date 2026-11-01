<?php

namespace Modules\Transparency\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignUpdate extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['campaign_id','title','content','media_url','published_at'];
    protected $casts = ['published_at'=>'datetime'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function campaign(){ return $this->belongsTo(\Modules\Catalog\Entities\Campaign::class); }
}
