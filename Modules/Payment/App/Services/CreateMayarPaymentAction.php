<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Carbon;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;

class CreateMayarPaymentAction
{
    public function __construct(private readonly MayarClient $client)
    {
    }

    public function execute(Donation $donation, string $name, string $email, string $mobile, ?string $redirectUrl = null): Payment
    {
        $expiresAt = now()->addHour();
        $payload = [
            'name' => $name,
            'amount' => (int) $donation->gross_amount,
            'email' => $email,
            'mobile' => $mobile,
            'description' => 'Donasi Salamaid'.($donation->campaign?->title ? ' - '.$donation->campaign->title : ''),
            'expiredAt' => $expiresAt->utc()->toIso8601String(),
            'extraData' => ['donationId' => $donation->id],
        ];

        if ($redirectUrl) {
            $payload['redirectUrl'] = $redirectUrl;
        }

        $data = $this->client->createPaymentRequest($payload);
        $transactionId = (string) ($data['transactionId'] ?? '');

        if ($transactionId === '' || empty($data['link'])) {
            throw new \RuntimeException('Response Mayar tidak memiliki transactionId atau link.');
        }

        return Payment::updateOrCreate(
            ['donation_id' => $donation->id],
            [
                'provider' => 'mayar',
                'order_id' => $transactionId,
                'channel_code' => 'mayar_checkout',
                'redirect_url' => $data['link'],
                'status' => 'pending',
                'expired_at' => isset($data['expiredAt']) ? Carbon::createFromTimestampMs((int) $data['expiredAt']) : $expiresAt,
                'raw_request_json' => $payload,
            ],
        );
    }
}
