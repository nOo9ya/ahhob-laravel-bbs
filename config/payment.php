<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | 기본 결제 게이트웨이를 설정합니다.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'inicis'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | 지원하는 결제 게이트웨이들의 설정입니다.
    |
    */

    'gateways' => [

        'inicis' => [
            'merchant_id' => env('INICIS_MERCHANT_ID'),
            'sign_key' => env('INICIS_SIGN_KEY'),
            'test_mode' => env('INICIS_TEST_MODE', true),
            'fee_rate' => env('INICIS_FEE_RATE', 2.9),
            'fee_fixed' => env('INICIS_FEE_FIXED', 0),
        ],

        'kg_inicis' => [
            'merchant_id' => env('KG_INICIS_MERCHANT_ID'),
            'merchant_key' => env('KG_INICIS_MERCHANT_KEY'),
            'test_mode' => env('KG_INICIS_TEST_MODE', true),
            'fee_rate' => env('KG_INICIS_FEE_RATE', 2.8),
            'fee_fixed' => env('KG_INICIS_FEE_FIXED', 0),
        ],

        'stripe' => [
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'test_mode' => env('STRIPE_TEST_MODE', true),
            'fee_rate' => env('STRIPE_FEE_RATE', 3.4),
            'fee_fixed' => env('STRIPE_FEE_FIXED', 30),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | 결제 수단별 설정입니다.
    |
    */

    'methods' => [
        'card' => [
            'name' => '신용카드',
            'icon' => 'credit-card',
            'sort_order' => 1,
        ],
        'bank' => [
            'name' => '계좌이체',
            'icon' => 'bank',
            'sort_order' => 2,
        ],
        'virtual_account' => [
            'name' => '가상계좌',
            'icon' => 'receipt',
            'sort_order' => 3,
        ],
        'phone' => [
            'name' => '휴대폰',
            'icon' => 'phone',
            'sort_order' => 4,
        ],
        'paypal' => [
            'name' => 'PayPal',
            'icon' => 'paypal',
            'sort_order' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    |
    | 결제 한도 설정입니다.
    |
    */

    'limits' => [
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 1000),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 10000000),
        'daily_limit' => env('PAYMENT_DAILY_LIMIT', 50000000),
        'monthly_limit' => env('PAYMENT_MONTHLY_LIMIT', 500000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | 결제 재시도 설정입니다.
    |
    */

    'retry' => [
        'max_attempts' => env('PAYMENT_MAX_RETRY', 3),
        'delay_seconds' => env('PAYMENT_RETRY_DELAY', 60),
        'backoff_multiplier' => env('PAYMENT_RETRY_BACKOFF', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | 보안 관련 설정입니다.
    |
    */

    'security' => [
        'encryption_key' => env('PAYMENT_ENCRYPTION_KEY'),
        'token_expiry' => env('PAYMENT_TOKEN_EXPIRY', 3600), // 1시간
        'webhook_timeout' => env('PAYMENT_WEBHOOK_TIMEOUT', 30),
        'ip_whitelist' => array_filter(explode(',', env('PAYMENT_IP_WHITELIST', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | 로깅 설정입니다.
    |
    */

    'logging' => [
        'enabled' => env('PAYMENT_LOGGING_ENABLED', true),
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'channel' => env('PAYMENT_LOG_CHANNEL', 'daily'),
        'days' => env('PAYMENT_LOG_DAYS', 30),
        'mask_sensitive_data' => env('PAYMENT_MASK_SENSITIVE_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | 통화 설정입니다.
    |
    */

    'currencies' => [
        'default' => 'KRW',
        'supported' => ['KRW', 'USD', 'EUR', 'JPY', 'CNY'],
        'exchange_rate_provider' => env('EXCHANGE_RATE_PROVIDER', 'fixer'),
    ],

];