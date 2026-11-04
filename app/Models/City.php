<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = ['province_id', 'code', 'name', 'type', 'sort_order', 'is_active'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
