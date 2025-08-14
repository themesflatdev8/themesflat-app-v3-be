<?php
return [
    'kafka_url' => env('KAFKA_CONNECT_URL'),
    'kafka_topic' => env('KAKA_TOPIC', 'products'),
    'kafka_group' => env('KAFKA_GROUP_ID', 'fether'),
    'app_env' => env('APP_ENV', 'local'),
    'hook_token' => env('HOOK_TOKEN', '12345'),
    'cache' => [
        'list_store_key' => 'list_store',
        'list_store_session' => 'list_store_session',
        'list_valid_sessions' => 'list_sessions',
        'store_detail_key' => 'store_detail',
        'count_result_product_key' => "product_count_result",
        'count_result_homepage_key' => "home_page_count_result",
        'count_result_link_key' => "link_count_result_",
        'count_result_online_store_page_key' => 'online_store_page_count_result',
        'count_result_delivery_method_key' => "delivery_method_count_result_",
        'count_result_payment_gateway_key' => "payment_gateway_count_result_",
        'list_store_rule' => 'store_translation_rule_',
        'list_store_rule_priority' => 'store_translation_rule_priority_',

    ]
];
