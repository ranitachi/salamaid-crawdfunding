<?php

namespace Modules\Payment\Entities;

use Illuminate\Database\Eloquent\Model;

class PaymentFee extends Model
{
    protected $fillable = ['payment_channel_id', 'fee_flat', 'fee_percent', 'min_fee', 'max_fee', 'effective_from', 'effective_to'];
    protected $casts = ['fee_percent' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }
}
