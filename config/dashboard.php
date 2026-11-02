<?php

return [
    // Disabled by default until production traffic justifies caching aggregates.
    'cache_seconds' => (int) env('ADMIN_DASHBOARD_CACHE_SECONDS', 0),
    'timezone' => env('ADMIN_DASHBOARD_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
    // Approved/requested payouts older than this are flagged on the dashboard.
    'payout_pending_days' => (int) env('ADMIN_DASHBOARD_PAYOUT_PENDING_DAYS', 3),
];
