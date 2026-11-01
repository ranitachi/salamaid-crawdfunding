<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\App\Enums\OrganizationStatus;

class Organization extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'slug','name','logo_url','description','address',
        'contact_email','contact_phone','bank_account_json','verified_at',
        'status','rejection_reason','suspension_reason','reviewed_by',
        'reviewed_at','suspended_at',
    ];

    protected $casts = [
        'bank_account_json' => 'array',
        'verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function members(){ return $this->hasMany(OrganizationMember::class); }
    public function campaigns(){ return $this->hasMany(\Modules\Catalog\Entities\Campaign::class); }
    public function reviewer(){ return $this->belongsTo(\App\Models\User::class, 'reviewed_by'); }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isVerified(): bool
    {
        return $this->status === OrganizationStatus::VERIFIED->value;
    }

    public function isPending(): bool
    {
        return $this->status === OrganizationStatus::PENDING->value;
    }

    public function isRejected(): bool
    {
        return $this->status === OrganizationStatus::REJECTED->value;
    }

    public function isSuspended(): bool
    {
        return $this->status === OrganizationStatus::SUSPENDED->value;
    }
}
