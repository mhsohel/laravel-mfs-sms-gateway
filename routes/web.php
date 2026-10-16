<?php

use Illuminate\Support\Facades\Route;
use Prantiksoft\MfsSmsGateway\Http\Controllers\MfsSmsPaymentController;
use Prantiksoft\MfsSmsGateway\Http\Controllers\MfsSmsWebhookController;

$prefix = config('mfs-sms.routes.web_prefix', 'mfs-sms');
$middleware = config('mfs-sms.routes.web_middleware', ['web']);

Route::group(['prefix' => $prefix, 'middleware' => $middleware], function () {
    // Customer automated payment screen
    Route::get('pay/{order_id}', [MfsSmsPaymentController::class, 'pay'])->name('mfs.sms.pay');

    // Status polling endpoint
    Route::get('status/{order_id}', [MfsSmsPaymentController::class, 'checkStatus'])->name('mfs.sms.status');

    // Manual TrxID verification fallback
    Route::post('manual-verify/{order_id}', [MfsSmsPaymentController::class, 'manualVerify'])->name('mfs.sms.manual_verify');

    // Extend timer
    Route::post('extend-timer/{order_id}', [MfsSmsPaymentController::class, 'extendTimer'])->name('mfs.sms.extend_timer');

    // Web test simulator (for admin / developer testing)
    Route::post('test-simulator', [MfsSmsWebhookController::class, 'testSimulator'])->name('mfs.sms.test_simulator');
});

