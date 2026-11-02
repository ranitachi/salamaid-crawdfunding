<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Entities\Campaign;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Services\CreateMayarPaymentAction;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\PaymentChannel;

class PublicDonationCheckoutService
{
    public function __construct(private readonly CreateMayarPaymentAction $createPayment) {}

    /** @return array{donation: Donation, payment: Payment} */
    public function create(Campaign $campaign, array $input): array
    {
        $gross = (int) $input['amount'];
        $feePlatform = 0;
        $feeChannel = 0;

        $donation = Donation::create([
            'campaign_id' => $campaign->getKey(),
            'user_id' => auth()->id(),
            'gross_amount' => $gross,
            'fee_platform' => $feePlatform,
            'fee_channel' => $feeChannel,
            'net_amount' => max(0, $gross - $feePlatform - $feeChannel),
            'method' => 'mayar',
            'anonymous' => (bool) ($input['anonymous'] ?? false),
            'message' => $input['message'] ?? null,
            'status' => 'pending',
        ]);

        $payment = $this->createPayment->execute($donation, $input['name'], $input['email'], $input['mobile']);

        return compact('donation', 'payment');
    }

    public function campaign(string $slug): ?Campaign
    {
        if (! Schema::hasTable('campaigns') || ! Schema::hasTable('organizations')) return null;

        return Campaign::query()->published()->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('status', 'verified'))
            ->with('organization:id,name,status')
            ->first();
    }

    public function channels()
    {
        if (! Schema::hasTable('payment_channels')) return collect();
        return PaymentChannel::query()->where('active', true)->orderBy('label')->get(['id', 'provider', 'code', 'method', 'label']);
    }
}
