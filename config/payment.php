<?php

return [
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
        ],
        'paid_statuses' => ['paid', 'settlement', 'success', '2'],
        'failed_statuses' => ['failed', 'deny', 'cancel', 'expired', 'expire', '3', '4'],
    ],
];
