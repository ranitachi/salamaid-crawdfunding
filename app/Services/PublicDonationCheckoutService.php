<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use RuntimeException;
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

        $statusUrl = URL::temporarySignedRoute('public.payments.status', now()->addDay(), ['donation' => $donation->getKey()]);
        $payment = $this->createPayment->execute($donation, $input['name'], $input['email'], $input['mobile'], $statusUrl);

        return compact('donation', 'payment');
    }

    public function campaign(string $slug): ?Campaign
    {
        if (! Schema::hasTable('campaigns') || ! Schema::hasTable('organizations')) return null;

        return Campaign::query()->published()->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('status', 'verified'))
            ->with('organization:id,name,slug,status,logo_url,banner_url,tagline,primary_color,secondary_color,description,address,contact_email,contact_phone,website_url,instagram_url,facebook_url,whatsapp_url')
            ->first();
    }

    public function regenerate(Donation $donation, ?string $mobile = null): Payment
    {
        $donation->loadMissing(['campaign', 'payment', 'user']);
        $payment = $donation->payment;

        if (! in_array((string) $donation->status, ['pending', 'expired'], true)
            || ($payment && $payment->provider !== 'mayar')
            || $payment?->status === 'paid') {
            throw new RuntimeException('Donasi ini tidak memerlukan link pembayaran baru.');
        }

        $request = is_array($payment?->raw_request_json) ? $payment->raw_request_json : [];
        $name = trim((string) ($request['name'] ?? $donation->user?->name));
        $email = trim((string) ($request['email'] ?? $donation->user?->email));
        $mobile = trim((string) ($mobile ?: ($request['mobile'] ?? '')));

        if ($name === '' || $email === '' || $mobile === '') {
            throw new RuntimeException('Data kontak donasi tidak lengkap untuk membuat link baru.');
        }

        $statusUrl = URL::temporarySignedRoute('public.payments.status', now()->addDay(), ['donation' => $donation->getKey()]);

        return $this->createPayment->execute($donation, $name, $email, $mobile, $statusUrl);
    }

    public function channels()
    {
        if (! Schema::hasTable('payment_channels')) return collect();
        return PaymentChannel::query()->where('active', true)->orderBy('label')->get(['id', 'provider', 'code', 'method', 'label']);
    }
}
