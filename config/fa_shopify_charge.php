<?php
return [
    'trial' => [
        'active' => true,
        'days' => 14,
    ],
    'plans_charge_test' => ['affiliate', 'partner_test', 'development', 'plus_partner_sandbox', 'open_learning'],
    'plans_charge_plus' => [ // các plan tính theo giá plus
        'unlimited',
        'shopify_plus',
        'business',
        'enterprise',
        'professional',
    ],
    'cancel_send_email' => ['affiliate', 'partner_test', 'development', 'plus_partner_sandbox', 'open_learning', 'staff'],
];
