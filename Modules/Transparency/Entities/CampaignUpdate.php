<?php

namespace Modules\Transparency\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Transparency\App\Enums\CampaignUpdateStatus;
use Modules\Transparency\App\Enums\CampaignUpdateType;

class CampaignUpdate extends Model
{
    public $incrementing = false; protected $keyType = 'string';

    /** Workflow fields (status, reviewer, timestamps) are written only by CampaignUpdateReviewService. */
    protected $fillable = ['campaign_id','type','payout_id','amount_used','title','content','media_url'];

    protected $attributes = ['type' => 'update', 'status' => 'draft'];

    protected $casts = [
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'amount_used' => 'integer',
    ];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function campaign(){ return $this->belongsTo(\Modules\Catalog\Entities\Campaign::class); }
    public function payout(){ return $this->belongsTo(\Modules\Operations\Entities\Payout::class); }
    public function attachments(){ return $this->hasMany(CampaignUpdateAttachment::class); }
    public function creator(){ return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function reviewer(){ return $this->belongsTo(\App\Models\User::class, 'reviewed_by'); }

    /** Only reviewed and published updates may be shown on public pages. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', CampaignUpdateStatus::PUBLISHED->value);
    }

    public function statusEnum(): ?CampaignUpdateStatus
    {
        return CampaignUpdateStatus::tryFrom((string) $this->status);
    }

    public function typeEnum(): ?CampaignUpdateType
    {
        return CampaignUpdateType::tryFrom((string) $this->type);
    }

    public function isFundReport(): bool
    {
        return $this->typeEnum() === CampaignUpdateType::FUND_REPORT;
    }

    public function isEditable(): bool
    {
        return (bool) $this->statusEnum()?->isEditable();
    }
}
