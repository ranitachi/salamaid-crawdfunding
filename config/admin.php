<?php

return [
    'route_prefix' => 'admin',

    'notifications' => [
        // Email notifications remain disabled unless explicitly enabled in .env.
        'email_enabled' => (bool) env('ADMIN_NOTIFICATION_EMAIL_ENABLED', false),
    ],

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'reviewer' => 'Reviewer',
        'finance' => 'Finance',
        'support' => 'Support',
        'organization_admin' => 'Admin Lembaga',
    ],

    /*
    | Permission matrix (docs/tahapan-admin.md, A2). Read through
    | User::hasAdminPermission(), the `admin.permission` middleware and policies.
    | Permissions granted only by '*' (super_admin): users.roles.manage.
    */
    'permissions' => [
        'super_admin' => ['*'],
        'admin' => [
            'dashboard.view', 'organizations.review', 'campaigns.review', 'notifications.view',
            'campaigns.publish', 'donations.view', 'payments.view', 'payouts.view',
            'transparency.review', 'categories.manage', 'help.manage', 'reports.view', 'audit.view',
        ],
        'reviewer' => [
            'dashboard.view', 'organizations.review', 'campaigns.review', 'notifications.view',
            'transparency.review', 'reports.view', 'audit.view',
        ],
        'finance' => [
            'dashboard.view', 'donations.view', 'payments.manage', 'notifications.view',
            'payouts.manage', 'reports.view', 'audit.view',
        ],
        'support' => [
            'dashboard.view', 'organizations.view', 'campaigns.view', 'notifications.view',
            'donations.view', 'payments.view', 'payouts.view', 'reports.view', 'audit.view',
        ],
        'organization_admin' => [
            'dashboard.view', 'campaigns.view', 'campaigns.manage', 'notifications.view', 'reports.view',
        ],
    ],

    'statuses' => [
        'campaign' => [
            'draft', 'under_review', 'published', 'closed', 'rejected', 'suspended',
        ],
        'donation' => ['pending', 'paid', 'expired', 'refund', 'failed'],
        'payment' => ['pending', 'paid', 'expired', 'failed'],
        'payout' => ['requested', 'approved', 'rejected', 'paid'],
        'organization' => ['pending', 'verified', 'rejected', 'suspended'],
    ],
];
