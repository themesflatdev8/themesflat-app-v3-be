<?php
return [
    'app_version' => "2.0",
    'app_name' => 'ts',
    'paginate_limit' => 20,
    'cdn_url' => env('CDN_URL'),
    'shopify_api_key' => env('SHOPIFY_API_KEY'),
    'shopify_api_secret' => env('SHOPIFY_API_SECRET'),
    'front_url' => env('FRONT_URL'),
    'app_key' => env('APP_KEY_ROOT'),
    'app_url' => env('APP_URL'),
    'hook_url' => env('APP_URL'),
    'trial_days' => 7,
    'embedded' => [
        'name' => ENV('EMBEDDED_APP_NAME')
    ],
    'ignore_variant' => "Default Title",
    'ignore_option' => "Title"
];
