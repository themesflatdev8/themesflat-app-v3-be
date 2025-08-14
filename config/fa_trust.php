<?php
return [
    'default_trust_badges' => [
        'visibility' => true,
        'heading' => 'We keep your information and payment safe',
        'icons' => [
            'free-shipping-gold-2.svg',
            'free-returns-gold-3.svg',
            'money-back-gold-3.svg',
            'premium-gold-2.svg',
            'satisfaction-gold-11.svg',
            'secure-checkout-gold-1.svg',
        ],
        'strategyType' => 'all', // hoặc 'specific_products'
        'strategySpecific' => [],
        'template' => 'inline', // hoặc 'box'
        'backgroundColor' => '#F9F9F9',
        'borderColor' => '#D9D9D9',
        'textColor' => '#111111',
        'headingSize' => 14,
        'desktop' => [
            'size' => 48,
        ],
        'mobile' => [
            'size' => 40,
        ],
    ],
    'default_trust_badges_on_cart' => [
        'visibility' => false,
        'heading' => 'We keep your information and payment safe',
        'icons' => [
            'free-shipping-gold-2.svg',
            'free-returns-gold-3.svg',
            'money-back-gold-3.svg',
            'premium-gold-2.svg',
            'satisfaction-gold-11.svg',
            'secure-checkout-gold-1.svg',
        ],
        'strategyType' => 'all', // hoặc 'specific_products'
        'strategySpecific' => [],
        'template' => 'inline', // hoặc 'box'
        'backgroundColor' => '#F9F9F9',
        'borderColor' => '#D9D9D9',
        'textColor' => '#111111',
        'headingSize' => 14,
        'position' => 'above', // hoặc 'below'
        'desktop' => [
            'size' => 48,
        ],
        'mobile' => [
            'size' => 40,
        ],
    ],
    'default_payment_badges' => [
        'visibility' => true,
        'heading' => 'Multiple secure payment options available',
        'icons' => [
            'mastercard_color_card.svg',
            'visa_1_color_card.svg',
            'applepay2_color_card.svg',
            'americanexpress_1_color_card.svg',
            'shoppay_color_card.svg',
        ],
        'strategyType' => 'all', // hoặc 'specific_products'
        'strategySpecific' => [],
        'template' => 'inline', // hoặc 'box'
        'backgroundColor' => '#F9F9F9',
        'borderColor' => '#D9D9D9',
        'textColor' => '#111111',
        'headingSize' => 14,
        'desktop' => [
            'size' => 48,
        ],
        'mobile' => [
            'size' => 40,
        ],
    ],
    'default_payment_badges_on_cart' => [
        'visibility' => false,
        'heading' => 'Multiple secure payment options available',
        'icons' => [
            'mastercard_color_card.svg',
            'visa_1_color_card.svg',
            'applepay2_color_card.svg',
            'americanexpress_1_color_card.svg',
            'shoppay_color_card.svg',
        ],
        'strategyType' => 'all', // hoặc 'specific_products'
        'strategySpecific' => [],
        'template' => 'inline', // hoặc 'box'
        'backgroundColor' => '#F9F9F9',
        'borderColor' => '#D9D9D9',
        'textColor' => '#111111',
        'headingSize' => 14,
        'position' => 'above', // hoặc 'below'
        'desktop' => [
            'size' => 48,
        ],
        'mobile' => [
            'size' => 40,
        ],
    ],
    'default_social_media_buttons' => [
        'visibility' => false,
        'socials' => [
            ['id' => 'facebook', 'label' => 'Facebook', 'link' => ''],
            ['id' => 'instagram', 'label' => 'Instagram', 'link' => ''],
            ['id' => 'tiktok', 'label' => 'Tiktok', 'link' => ''],
            ['id' => 'youtube', 'label' => 'Youtube', 'link' => ''],
            ['id' => 'x', 'label' => 'X', 'link' => ''],
            ['id' => 'linkedin', 'label' => 'Linkedin', 'link' => ''],
            ['id' => 'discord', 'label' => 'Discord', 'link' => ''],
            ['id' => 'snapchat', 'label' => 'Snapchat', 'link' => ''],
            ['id' => 'pinterest', 'label' => 'Pinterest', 'link' => ''],
            ['id' => 'tumblr', 'label' => 'Tumblr', 'link' => ''],
        ],
        'desktop' => [
            'visibility' => true,
            'template' => 'circle', // square
            'size' => 40,
            'position' => 'bottom_right', // bottom_left
            'positionBottom' => 20,
            'positionLeft' => 20,
            'positionRight' => 20,
            'style' => 'vertical' // horizontal
        ],
        'mobile' => [
            'visibility' => true,
            'template' => 'circle', // square
            'size' => 32,
            'position' => 'bottom_right', // bottom_left
            'positionBottom' => 20,
            'positionLeft' => 20,
            'positionRight' => 20,
            'style' => 'vertical' // horizontal
        ]
    ],
    'default_sales_popup' => [
        'visibility' => false,
        'lastOrders' => 7,
        'statusDisplayOrder' => 'all', // open, archived
        'text' => '{customer_full_name} from {city}, {country_code} bought\n{product_name}\n{time_ago}',
        'placement' => 'all', // specific
        'specificPages' => ['index', 'product'], // cart, collection, collection_list
        'timingFirst' => 5,
        'timingDelay' => 10,
        'timingDuration' => 5,
        'backgroundColor' => '#333333',
        'borderColor' => '#0000001f',
        'textColor' => '#FFFFFF',
        'desktop' => [
            'visibility' => true,
            'position' => 'bottom_left', // bottom_right, top_left, top_right
            'positionTop' => 20,
            'positionBottom' => 20,
            'positionLeft' => 20,
            'positionRight' => 20,
        ],
        'mobile' => [
            'visibility' => true,
            'position' => 'top', // bottom
        ],
    ]
];
