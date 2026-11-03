<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HelpCategory extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];
    protected $casts = ['sort_order' => 'integer', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            $category->id ??= (string) Str::uuid();
        });
    }

    public function articles()
    {
        return $this->hasMany(HelpArticle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
