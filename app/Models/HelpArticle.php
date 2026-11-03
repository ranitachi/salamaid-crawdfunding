<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HelpArticle extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['help_category_id', 'category', 'question', 'answer', 'sort_order', 'is_published'];

    protected $casts = ['is_published' => 'boolean', 'sort_order' => 'integer'];

    public function helpCategory()
    {
        return $this->belongsTo(HelpCategory::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $article): void {
            $article->id ??= (string) Str::uuid();
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
