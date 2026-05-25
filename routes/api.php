<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Invoices\InvoiceController;
use App\Http\Controllers\Invoices\InvoiceItemController;
use App\Http\Controllers\Invoices\InvoicePdfController;
use App\Http\Controllers\Invoices\InvoiceSendController;
use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Tenants\TenantController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Http\Controllers\Webhooks\PayPalWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('webhooks.mercadopago');
Route::post('/webhooks/paypal', PayPalWebhookController::class)
    ->name('webhooks.paypal');

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('invoice-items', InvoiceItemController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::post('/payments/{payment}/checkout', CheckoutController::class);
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show']);
    Route::post('/invoices/{invoice}/send', InvoiceSendController::class);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/invoices-summary', [ReportController::class, 'invoicesSummary']);
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv']);
    Route::get('/users', [UserController::class, 'index']);
});
