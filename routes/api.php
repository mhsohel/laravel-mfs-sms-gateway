<?php

use Illuminate\Support\Facades\Route;
use Prantiksoft\MfsSmsGateway\Http\Controllers\MfsSmsWebhookController;

$prefix = config('mfs-sms.routes.api_prefix', 'api/mfs-sms');
$middleware = config('mfs-sms.routes.api_middleware', ['api']);

Route::group(['prefix' => $prefix, 'middleware' => $middleware], function () {
    // Unified webhook endpoint (auto-detects bKash, Nagad, Rocket from SMS body or sender)
    Route::match(['get', 'post'], 'webhook', [MfsSmsWebhookController::class, 'handle'])->name('mfs.sms.webhook');

    // Provider-specific webhook endpoints
    Route::match(['get', 'post'], '{provider}/webhook', [MfsSmsWebhookController::class, 'handle'])
        ->where('provider', 'bkash|nagad|rocket|upay')
        ->name('mfs.sms.provider_webhook');

    // API simulator endpoint (for backend/CLI/Postman developer testing)
    Route::post('test-simulator', [MfsSmsWebhookController::class, 'testSimulator'])->name('mfs.sms.api_test_simulator');
});
