<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['name','slug','icon','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted() {
        static::creating(function($m){
            $m->id ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
        });
    }

    public function campaigns()
    {
        return $this->hasMany(\Modules\Catalog\Entities\Campaign::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
