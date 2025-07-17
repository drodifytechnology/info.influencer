<?php

use App\Models\User;
use App\Http\Controllers as Web;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/auth.php';

Route::redirect('/', '/login');

//package order payment
Route::get('/payment-gateways/{order_id}', [Web\PackagePaymentController::class, 'index'])->name('payment-gateways.index');
Route::post('/payment/{order_id}/{gateway_id}', [Web\PackagePaymentController::class, 'payment'])->name('payment-gateways.payment');
Route::get('/payments/success', [Web\PackagePaymentController::class, 'success'])->name('payments.success');
Route::get('/payments/failed', [Web\PackagePaymentController::class, 'failed'])->name('payments.failed');
Route::post('ssl-commerz/payments/success', [Web\PackagePaymentController::class, 'sslCommerzSuccess']);
Route::post('ssl-commerz/payments/failed', [Web\PackagePaymentController::class, 'sslCommerzFailed']);
Route::get('/package-order-status', [Web\PackagePaymentController::class, 'orderStatus'])->name('package.order.status');
 
//order
Route::get('/payments-gateways/{order_id}', [Web\PaymentController::class, 'index'])->name('payments-gateways.index');
Route::post('/payments/{order_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->name('payments-gateways.payment');
Route::get('/payment/success', [Web\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [Web\PaymentController::class, 'failed'])->name('payment.failed');
Route::post('ssl-commerz/payment/success', [Web\PaymentController::class, 'sslCommerzSuccess']);
Route::post('ssl-commerz/payment/failed', [Web\PaymentController::class, 'sslCommerzFailed']);
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])->name('order.status');

Route::get('/',  [AuthController::class, 'redirectToInstagram']);
Route::get('/login-with-insta',  [AuthController::class, 'redirectToInstagram']);
Route::get('/auth/instagram/callback', [AuthController::class, 'handleInstagramCallback']);


Route::get('/privacy-policy', function () {
    return view('policy_privacy');
});
Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    return back()->with('success', __('Cache has been cleared.'));
});



Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'Migrated';
});
