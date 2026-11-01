<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    public $incrementing = false; protected $keyType = 'string';
    protected $fillable = ['name','slug','icon'];

    protected static function booted() {
        static::creating(function($m){
            $m->id ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
        });
    }
}
