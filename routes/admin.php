<?php

use Illuminate\Support\Facades\Route;
use Prantiksoft\MfsSmsGateway\Http\Controllers\MfsSmsAdminController;

$adminPrefix = config('mfs-sms.routes.admin_prefix', 'admin/mfs-sms');
$adminMiddleware = config('mfs-sms.routes.admin_middleware', ['web', 'auth']);

Route::group(['prefix' => $adminPrefix, 'middleware' => $adminMiddleware], function () {
    Route::get('/', [MfsSmsAdminController::class, 'index'])->name('mfs.sms.admin.index');
    Route::post('save/{provider}', [MfsSmsAdminController::class, 'saveProvider'])->name('mfs.sms.admin.save');
    Route::post('toggle/{provider}', [MfsSmsAdminController::class, 'toggleProvider'])->name('mfs.sms.admin.toggle');
    Route::post('generate-token/{provider}', [MfsSmsAdminController::class, 'generateToken'])->name('mfs.sms.admin.generate_token');
    Route::get('transactions', [MfsSmsAdminController::class, 'transactions'])->name('mfs.sms.admin.transactions');
    Route::post('transactions/{id}/match', [MfsSmsAdminController::class, 'manualMatch'])->name('mfs.sms.admin.manual_match');
});
