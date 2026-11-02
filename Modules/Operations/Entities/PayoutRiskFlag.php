<?php

namespace Modules\Operations\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PayoutRiskFlag extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['payout_id', 'organization_id', 'code', 'severity', 'status', 'reason', 'metadata', 'reviewed_by', 'reviewed_at'];
    protected $casts = ['metadata' => 'array', 'reviewed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $flag) => $flag->id ??= (string) Str::uuid());
    }

    public function payout() { return $this->belongsTo(Payout::class); }
    public function organization() { return $this->belongsTo(\Modules\Core\Entities\Organization::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
