<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Revenue Settings
    |--------------------------------------------------------------------------
    */

    // % platform takes on token content purchases (chapters, editorials)
    'content_cut' => 10,

    // % platform takes on direct book sales
    'book_cut' => 10,

    // % platform takes on creator withdrawal
    'withdrawal_tax' => 1,

    // max $ platform takes per withdrawal regardless of amount
    'withdrawal_tax_cap' => 5.00,

    /*
    |--------------------------------------------------------------------------
    | Stripe Settings
    |--------------------------------------------------------------------------
    */

    'stripe_percentage' => 2.9,
    'stripe_fixed'      => 0.30,

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    */

    // 1 token = $1 USD
    'token_to_usd' => 1.00,

    // minimum withdrawal amount in USD
    'min_withdrawal' => 10.00,

    /*
    |--------------------------------------------------------------------------
    | Token Packages
    |--------------------------------------------------------------------------
    */

    'token_packages' => [
        [
            'name'        => 'Starter',
            'tokens'      => 10,
            'price_usd'   => 10.62,
            'description' => 'Perfect for trying out content',
        ],
        [
            'name'        => 'Standard',
            'tokens'      => 50,
            'price_usd'   => 52.10,
            'description' => 'Great for regular readers',
            'popular'     => true,
        ],
        [
            'name'        => 'Pro',
            'tokens'      => 100,
            'price_usd'   => 103.90,
            'description' => 'Best value for power readers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Book Policy
    |--------------------------------------------------------------------------
    */

    // allow writers to enable chapter-to-book completion pricing
    'allow_chapter_completion' => true,
];