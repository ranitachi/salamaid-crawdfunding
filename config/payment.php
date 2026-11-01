<?php

return [
    'mayar' => [
        'base_url' => env('MAYAR_API_BASE_URL', env('MAYAR_ENVIRONMENT', 'sandbox') === 'production'
            ? 'https://api.mayar.id/hl/v2'
            : 'https://api.mayar.io/hl/v2'),
        'api_key' => env('MAYAR_API_KEY'),
        'webhook_token' => env('MAYAR_WEBHOOK_TOKEN'),
        'webhook_token_query' => env('MAYAR_WEBHOOK_TOKEN_QUERY', 'token'),
    ],
    'webhooks' => [
        'providers' => [
            'generic' => [
                'algorithm' => 'sha256',
                'secret' => env('PAYMENT_WEBHOOK_GENERIC_SECRET'),
                'signature_header' => 'X-Webhook-Signature',
                'order_id_field' => 'order_id',
                'event_field' => 'event',
                'status_field' => 'status',
            ],
            'faspay' => [
                'algorithm' => 'sha256',
                'secret' => env('FASPAY_WEBHOOK_SECRET'),
                'signature_header' => 'X-Faspay-Signature',
                'order_id_field' => 'bill_no',
                'event_field' => 'event',
                'status_field' => 'payment_status',
            ],
            'mayar' => [
                'mode' => 'token_query',
                'token' => env('MAYAR_WEBHOOK_TOKEN'),
                'order_id_field' => 'data.transactionId',
                'event_field' => 'event',
                'status_field' => 'data.status',
            ],
        ],
        'paid_statuses' => ['paid', 'settlement', 'success', '2'],
        'failed_statuses' => ['failed', 'deny', 'cancel', 'expired', 'expire', '3', '4'],
    ],
];
