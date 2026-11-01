<?php

namespace Modules\Payment\App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Entities\PaymentChannel;
use Modules\Payment\Entities\PaymentFee;

class PaymentChannelManagementService
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    public function saveChannel(array $attributes, ?PaymentChannel $channel, $actor): PaymentChannel
    {
        $channel ??= new PaymentChannel();
        $before = $channel->exists ? $channel->only(['provider', 'code', 'method', 'label', 'active']) : [];
        $channel->fill($attributes)->save();
        $this->audit->log($before ? 'payment_channel.updated' : 'payment_channel.created', $channel, $before, $channel->only(['provider', 'code', 'method', 'label', 'active']), null, $actor, ['module' => 'Payment', 'entity' => 'payment_channel']);
        return $channel;
    }

    public function setActive(PaymentChannel $channel, bool $active, $actor): PaymentChannel
    {
        if ($channel->active === $active) return $channel;
        $before = ['active' => $channel->active];
        $channel->update(['active' => $active]);
        $this->audit->log($active ? 'payment_channel.activated' : 'payment_channel.deactivated', $channel, $before, ['active' => $active], null, $actor, ['module' => 'Payment', 'entity' => 'payment_channel']);
        return $channel->refresh();
    }

    public function saveFee(PaymentChannel $channel, array $attributes, $actor): PaymentFee
    {
        $overlap = PaymentFee::query()->where('payment_channel_id', $channel->id)
            ->where(function ($query) use ($attributes) {
                $to = $attributes['effective_to'] ?? '9999-12-31';
                $query->whereDate('effective_from', '<=', $to)->where(function ($query) use ($attributes) {
                    $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $attributes['effective_from']);
                });
            })->exists();
        if ($overlap) throw new \InvalidArgumentException('Periode fee bertumpang tindih dengan fee yang sudah ada.');

        return DB::transaction(function () use ($channel, $attributes, $actor) {
            $fee = $channel->fees()->create($attributes);
            $this->audit->log('payment_fee.created', $fee, [], $fee->only(['fee_flat', 'fee_percent', 'min_fee', 'max_fee', 'effective_from', 'effective_to']), null, $actor, ['module' => 'Payment', 'entity' => 'payment_fee', 'channel_id' => $channel->id]);
            return $fee;
        });
    }
}
