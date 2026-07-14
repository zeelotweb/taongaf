<?php
return [
    // Standard split
    'publisher_share'      => 90,
    'platform_content_cut' => 10,

    // Profile commerce split
    'hustler_base'         => 85,
    'hustler_commission'   => 7,
    'profile_owner_share'  => 5,
    'platform_commerce_cut'=> 3,

    // Publisher hustling their own work bonus
    // hustler_base + hustler_commission = 92 tokens
    // automatically applied when publisher_id === hustler_id

    // Unlock thresholds
    'hustle_unlock' => [
        'min_earnings'  => 500,
        'min_followers' => 100,
    ],

    'profile_commerce_unlock' => [
        'min_followers' => 5000,
        'min_earnings'  => 2000,
    ],

    'referral_window_hours'  => 48,
    'withdrawal_credit_rate' => 50,

    'service_types' => [
        'promotion'  => ['label' => 'Content promotion', 'fee_type' => 'flat'],
        'sale_refer' => ['label' => 'Sales referral',    'fee_type' => 'commission'],
    ],
];