<?php

namespace Modules\Operations\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = [
        'organization_id','amount','status','requested_by','approved_by','paid_at',
        'evidence_url','evidence_path','evidence_mime','evidence_size','note',
    ];
    protected $casts = ['paid_at'=>'datetime', 'amount'=>'integer', 'evidence_size'=>'integer'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function organization(){ return $this->belongsTo(\Modules\Core\Entities\Organization::class); }
    public function requester(){ return $this->belongsTo(\App\Models\User::class, 'requested_by'); }
    public function approver(){ return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function riskFlags(){ return $this->hasMany(PayoutRiskFlag::class); }

    /** Private storage directory that may hold this payout's evidence. */
    public function evidenceDirectory(): string
    {
        return trim((string) config('operations.evidence.directory'), '/').'/'.$this->getKey();
    }

    /** New format: file on the private disk, inside this payout's own directory. */
    public function hasPrivateEvidence(): bool
    {
        return filled($this->evidence_path)
            && Str::startsWith((string) $this->evidence_path, $this->evidenceDirectory().'/');
    }

    /** Legacy format: external http(s) URL recorded before private storage existed. */
    public function hasLegacyEvidenceUrl(): bool
    {
        return filled($this->evidence_url)
            && in_array(strtolower((string) parse_url((string) $this->evidence_url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public function hasEvidence(): bool
    {
        return $this->hasPrivateEvidence() || $this->hasLegacyEvidenceUrl();
    }
}
