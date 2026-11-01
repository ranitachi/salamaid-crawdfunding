<?php

namespace Modules\Payment\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $fillable = ['provider', 'code', 'method', 'label', 'active', 'meta_json'];
    protected $casts = ['active' => 'boolean', 'meta_json' => 'array'];

    public function fees()
    {
        return $this->hasMany(PaymentFee::class);
    }
}
