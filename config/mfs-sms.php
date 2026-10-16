<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Route & Middleware Settings
    |--------------------------------------------------------------------------
    |
    | Define the route prefix and middlewares used for customer-facing payment
    | screens and incoming webhook endpoints.
    |
    */
    'routes' => [
        'web_prefix'       => 'mfs-sms',
        'api_prefix'       => 'api/mfs-sms',
        'admin_prefix'     => 'admin/mfs-sms',
        'web_middleware'   => ['web'],
        'api_middleware'   => ['api'],
        'admin_middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MFS Gateway Wallets Configuration
    |--------------------------------------------------------------------------
    |
    | Set your recipient wallet numbers, account types (Personal / Merchant /
    | Agent / Uddokta), and webhook security tokens for each MFS provider.
    |
    */
    'gateways' => [
        'bkash' => [
            'enabled'      => env('BKASH_SMS_ENABLED', true),
            'title'        => 'bKash Direct (Auto-Verified via SMS)',
            'number'       => env('BKASH_SMS_NUMBER', '01735254295'),
            'type'         => env('BKASH_SMS_TYPE', 'Personal'), // Personal, Merchant, Agent
            'webhook_token'=> env('BKASH_SMS_WEBHOOK_TOKEN', 'mfs_secret_token_bkash_2026'),
            'brand_color'  => '#e2136e',
            'ussd_code'    => '*247#',
            'sender_id'    => 'bKash',
        ],

        'nagad' => [
            'enabled'      => env('NAGAD_SMS_ENABLED', true),
            'title'        => 'Nagad Direct (Auto-Verified via SMS)',
            'number'       => env('NAGAD_SMS_NUMBER', '01735254295'),
            'type'         => env('NAGAD_SMS_TYPE', 'Personal'), // Personal, Merchant, Uddokta
            'webhook_token'=> env('NAGAD_SMS_WEBHOOK_TOKEN', 'mfs_secret_token_nagad_2026'),
            'brand_color'  => '#f04923',
            'ussd_code'    => '*167#',
            'sender_id'    => 'NAGAD',
        ],

        'rocket' => [
            'enabled'      => env('ROCKET_SMS_ENABLED', false),
            'title'        => 'Rocket Direct (Auto-Verified via SMS)',
            'number'       => env('ROCKET_SMS_NUMBER', '01735254295-9'),
            'type'         => env('ROCKET_SMS_TYPE', 'Personal'),
            'webhook_token'=> env('ROCKET_SMS_WEBHOOK_TOKEN', 'mfs_secret_token_rocket_2026'),
            'brand_color'  => '#8c2d82',
            'ussd_code'    => '*322#',
            'sender_id'    => '16216',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Integration & Auto-Matching Settings
    |--------------------------------------------------------------------------
    |
    | Define how the package identifies and updates orders upon payment.
    |
    */
    'order' => [
        // Eloquent Model representing the order
        'model' => env('MFS_ORDER_MODEL', 'App\\Models\\Order'),

        // Primary key column of your Order model
        'primary_key' => 'id',

        // Invoice / Order number column (if different from primary key)
        'invoice_column' => 'invoice_id',

        // Amount column on your Order model
        'amount_column' => 'amount',

        // Customer phone column on your Order model
        'phone_column' => 'phoneNumber', // or 'customer_phone', 'phone', etc.

        // Order status column on your Order model
        'status_column' => 'order_status',

        // Payment status column on your Order model
        'payment_status_column' => 'payment_status', // 'paid', 'unpaid', etc.

        // Payment method column on your Order model
        'payment_method_column' => 'payment_method',

        // Value to assign to status_column when payment succeeds (e.g. 7, 'processing', 'completed')
        'paid_status_value' => env('MFS_PAID_STATUS_VALUE', 7),

        // Value to assign to payment_status_column (e.g. 'paid', 'Success', 1)
        'paid_payment_status_value' => env('MFS_PAID_PAYMENT_STATUS_VALUE', 'paid'),

        // Reference prefix displayed to customers during checkout
        'reference_prefix' => env('MFS_REF_PREFIX', 'Order'),

        // Payment timeout duration in minutes before expiry
        'timeout_minutes' => env('MFS_TIMEOUT_MINUTES', 15),

        // Success redirect URL pattern (e.g. '/customer/order-success/{order_id}' or route name)
        'success_url' => '/customer/order-success/{order_id}',

        // Cancel / Fail redirect URL pattern
        'cancel_url' => '/checkout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Order Resolver Callback
    |--------------------------------------------------------------------------
    |
    | If your application has complex order finding or post-payment logic, you
    | can define a custom closure or service class to execute when payment matches.
    |
    */
    'hooks' => [
        // Callable or class method e.g. [App\Services\PaymentService::class, 'handleMfsPayment']
        'on_payment_success' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'table_name'          => 'mfs_sms_transactions',
    'settings_table_name' => 'mfs_sms_settings',

];
