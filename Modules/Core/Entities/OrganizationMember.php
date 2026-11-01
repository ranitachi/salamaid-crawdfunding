<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrganizationMember extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['organization_id','user_id','role'];
    protected static function booted() {
        static::creating(fn($m)=> $m->id ??= (string) Str::uuid());
    }

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function user(){ return $this->belongsTo(\App\Models\User::class); }
}
