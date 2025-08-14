<?php

return [
    'template_version' => 1,
    // product : 10322426167665878671
    // local : 2739863457666753907
    'app_embed' => [
        'id_block' => env('ID_BLOCK_APP_EMBED', '3175351436053067797'),
        'path' => 'config/settings_data.json',

    ],

    'app_block' => [
        'id_block' => env('ID_BLOCK_APP_EMBED', '10322426167665878671'),
        "10322426167665878671" => "243b337f-c354-4228-a2fc-5a64abc9e714", // prod
        "2739863457666753907" => "f8b9fb01-4a70-443f-bdf5-6066e004aa5a", // local
    ],
    'app_block_quantity' => [
        'id_block' => env('ID_BLOCK_APP_EMBED', '10322426167665878671'),
    ],
    'setting_default' => [
        'visibility' => true,
        'themeOne' => [
            'themeName' => 'Template Slider',
            'template' => '1',
            'title' => 'Frequently Bought Together',
            'subTitle' => '',
            'contentTotal' => 'Total price:',
            'contentSave' => 'Save',
            'contentAddToCartButton' => 'Add to cart',
            'useQuantity' => 0,
            'cardBackgroundColor' => '#FFFFFF',
            'primaryColor' => '#1e1212',
            'secondaryColor' => '#FFFFFF',
            'borderColor' => '#0000000d',
            'outstandColor' => '#fc3f15',
            'borderRadius' => 8,
            'imageFit' => 'contain',
        ],
        'themeTwo' => [
            'themeName' => 'Template Popular',
            'template' => '2',
            'title' => 'Frequently Bought Together',
            'subTitle' => '',
            'contentTotal' => 'Total price:',
            'contentSave' => 'Save',
            'contentAddToCartButton' => 'Add to cart',
            'useQuantity' => 0,
            'cardBackgroundColor' => '#FFFFFF',
            'primaryColor' => '#1e1212',
            'secondaryColor' => '#FFFFFF',
            'borderColor' => '#0000000d',
            'outstandColor' => '#fc3f15',
            'borderRadius' => 8,
            'imageFit' => 'contain',
        ],
        'themeThree' => [
            'themeName' => 'Template Amazon',
            'template' => '3',
            'title' => 'Frequently Bought Together',
            'subTitle' => '',
            'contentTotal' => 'Total price:',
            'contentSave' => 'Save',
            'contentAddToCartButton' => 'Add to cart',
            'useQuantity' => 0,
            'cardBackgroundColor' => '#FFFFFF',
            'primaryColor' => '#1e1212',
            'secondaryColor' => '#FFFFFF',
            'borderColor' => '#0000000d',
            'outstandColor' => '#fc3f15',
            'borderRadius' => 8,
            'imageFit' => 'contain',
        ],
    ],
    'cart_setting_default' => [],
    'search_setting_default' => [],
    'setting_quantity_default' => [
        'visibility' => 1,
        'title' => 'Buy More, Save More!',
        'contentMostPopular' => 'Most popular',
        'contentChooseVariantTitle' => 'Choose variant',
        'contentChooseVariantButton' => 'Choose this variant',
        'contentAddToCartButton' => 'Add to cart',
        'position' => 'above', // hoặc 'below'
        'override' => 1,
        'cardBackgroundColor' => '#FFFFFF',
        'primaryColor' => '#1e1212',
        'secondaryColor' => '#FFFFFF',
        'borderColor' => '#0000000d',
        'outstandColor' => '#fc3f15',
        'borderRadius' => 8,
    ]
];
