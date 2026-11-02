<?php

return [
    'risk' => [
        'large_payout_amount' => (int) env('PAYOUT_RISK_LARGE_AMOUNT', 100000000),
    ],
    'name' => 'Operations',

    /*
    | Payout transfer evidence (bukti transfer). Stored on a private disk and
    | served only through the authorized download route.
    */
    'evidence' => [
        'disk' => env('PAYOUT_EVIDENCE_DISK', 'private'),
        'directory' => 'payouts/evidence',
        'max_kilobytes' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        'mimetypes' => ['image/jpeg', 'image/png', 'application/pdf'],
    ],
];
