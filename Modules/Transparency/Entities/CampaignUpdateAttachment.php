<?php

namespace Modules\Transparency\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignUpdateAttachment extends Model
{
    public $incrementing = false; protected $keyType = 'string';

    protected $guarded = ['*'];

    protected $casts = ['size' => 'integer'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function campaignUpdate(){ return $this->belongsTo(CampaignUpdate::class, 'campaign_update_id'); }
    public function uploader(){ return $this->belongsTo(\App\Models\User::class, 'uploaded_by'); }
}
