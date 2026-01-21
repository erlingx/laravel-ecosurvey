<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define subscription tiers with their features and limits.
    | Price IDs come from Stripe Dashboard (Test Mode).
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'stripe_price_id' => null,
            'limits' => [
                'data_points' => 50,
                'satellite_analyses' => 10,
                'report_exports' => 2,
            ],
            'features' => [
                'Basic maps',
                'Limited satellite data',
                'Community support',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 29,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'limits' => [
                'data_points' => 500,
                'satellite_analyses' => 100,
                'report_exports' => 20,
            ],
            'features' => [
                'All maps and visualization',
                'Full satellite indices (7)',
                'Advanced analytics',
                'Priority support',
                'Export to CSV/PDF',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 99,
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'limits' => [
                'data_points' => PHP_INT_MAX,
                'satellite_analyses' => PHP_INT_MAX,
                'report_exports' => PHP_INT_MAX,
            ],
            'features' => [
                'Unlimited everything',
                'API access',
                'White-label option',
                'Custom integrations',
                'SLA guarantee',
                'Dedicated support',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Requests per hour for each subscription tier.
    |
    */

    'rate_limits' => [
        'free' => 60,
        'pro' => 300,
        'enterprise' => 1000,
    ],
];
