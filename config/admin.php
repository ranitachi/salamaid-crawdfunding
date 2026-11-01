<?php

return [
    'route_prefix' => 'admin',

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'reviewer' => 'Reviewer',
        'finance' => 'Finance',
        'support' => 'Support',
    ],

    /*
    | Permission matrix (docs/tahapan-admin.md, A2). Read through
    | User::hasAdminPermission(), the `admin.permission` middleware and policies.
    | Permissions granted only by '*' (super_admin): users.roles.manage.
    */
    'permissions' => [
        'super_admin' => ['*'],
        'admin' => [
            'dashboard.view', 'organizations.review', 'campaigns.review',
            'campaigns.publish', 'donations.view', 'payments.view', 'payouts.view',
            'transparency.review', 'categories.manage', 'reports.view', 'audit.view',
        ],
        'reviewer' => [
            'dashboard.view', 'organizations.review', 'campaigns.review',
            'transparency.review', 'reports.view', 'audit.view',
        ],
        'finance' => [
            'dashboard.view', 'donations.view', 'payments.manage',
            'payouts.manage', 'reports.view', 'audit.view',
        ],
        'support' => [
            'dashboard.view', 'organizations.view', 'campaigns.view',
            'donations.view', 'payments.view', 'payouts.view', 'reports.view', 'audit.view',
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
