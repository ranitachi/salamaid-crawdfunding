<?php

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Catalog\App\Enums\CampaignStatus;

class Campaign extends Model
{
    use SoftDeletes;

    public $incrementing = false; protected $keyType = 'string';

    protected $fillable = [
        'organization_id','category_id','title','slug','description',
        'target_amount','collected_amount','deadline_at','location_label',
        'cover_url','status','is_featured','meta_json',
        'rejection_reason','suspension_reason','reviewed_by','reviewed_at',
        'published_at','closed_at','featured_at',
    ];

    protected $casts = [
        'meta_json'=>'array',
        'is_featured'=>'boolean',
        'deadline_at'=>'datetime',
        'reviewed_at'=>'datetime',
        'published_at'=>'datetime',
        'closed_at'=>'datetime',
        'featured_at'=>'datetime',
    ];

    protected static function booted() {
        static::creating(function($m){
            $m->id ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->title).'-'.Str::random(6);
        });
    }

    public function organization(){ return $this->belongsTo(\Modules\Core\Entities\Organization::class); }
    public function category(){ return $this->belongsTo(\Modules\Core\Entities\Category::class); }
    public function media(){ return $this->hasMany(CampaignMedia::class); }
    public function donations(){ return $this->hasMany(\Modules\Donation\Entities\Donation::class); }
    public function updates(){ return $this->hasMany(\Modules\Transparency\Entities\CampaignUpdate::class); }
    public function reviewer(){ return $this->belongsTo(\App\Models\User::class, 'reviewed_by'); }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePublished($query)
    {
        return $query->where('status', CampaignStatus::PUBLISHED->value);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function isPublished(): bool
    {
        return $this->status === CampaignStatus::PUBLISHED->value;
    }

    public function isUnderReview(): bool
    {
        return $this->status === CampaignStatus::UNDER_REVIEW->value;
    }

    public function isDraft(): bool
    {
        return $this->status === CampaignStatus::DRAFT->value;
    }

    public function isSuspended(): bool
    {
        return $this->status === CampaignStatus::SUSPENDED->value;
    }

    public function hasDonations(): bool
    {
        return $this->donations()->exists();
    }

    public function canBePermanentlyDeleted(): bool
    {
        return ! $this->isPublished() && ! $this->hasDonations();
    }
}
