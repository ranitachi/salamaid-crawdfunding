<?php

namespace Modules\Operations\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['organization_id','amount','status','requested_by','approved_by','paid_at','evidence_url','note'];
    protected $casts = ['paid_at'=>'datetime'];

    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function organization(){ return $this->belongsTo(\Modules\Core\Entities\Organization::class); }
    public function requester(){ return $this->belongsTo(\App\Models\User::class, 'requested_by'); }
    public function approver(){ return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}
