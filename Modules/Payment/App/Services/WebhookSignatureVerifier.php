<?php

namespace Modules\Payment\App\Services;

use Illuminate\Http\Request;

class WebhookSignatureVerifier
{
    public function verify(Request $request, string $provider): bool
    {
        $settings = config("payment.webhooks.providers.{$provider}");

        if (($settings['mode'] ?? null) === 'token_query') {
            $queryKey = config('payment.mayar.webhook_token_query', 'token');
            $provided = (string) $request->query($queryKey, '');
            $expected = (string) ($settings['token'] ?? '');

            return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
        }

        if (! is_array($settings) || blank($settings['secret'] ?? null)) {
            return false;
        }

        $header = $settings['signature_header'] ?? 'X-Webhook-Signature';
        $provided = trim((string) $request->header($header));

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac(
            (string) ($settings['algorithm'] ?? 'sha256'),
            $request->getContent(),
            (string) $settings['secret'],
        );

        return hash_equals($expected, $provided);
    }

    public function settings(string $provider): array
    {
        return (array) config("payment.webhooks.providers.{$provider}", []);
    }
}
