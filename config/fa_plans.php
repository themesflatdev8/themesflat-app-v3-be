<?php
return [
    'free' => [
        'price' => 0,
        'price_plus' => 0,
        'name' => 'free',
        'title' => 'Free',
        'next_plan' => 'essential',
        'languages_limit' => 2,
        'used_charge' => 0,
    ],
    'essential' => [
        'price' => 9.00,
        'price_plus' => 0,
        'name' => 'essential',
        'title' => 'Essential',
        'next_plan' => 'essential',
        'languages_limit' => 20,
        'used_charge' => 0,
    ]
    // 'premium' => [
    //     'price' => 29.00,
    //     'price_plus' => 0,
    //     'name' => 'premium',
    //     'title' => 'premium',
    //     'next_plan' => 'premium',
    //     'languages_limit' => 20,
    //     'used_charge' => 0,
    // ]
];
