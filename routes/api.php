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

    Route::get('/clients', [ClientController::class, 'index'])->middleware('role:viewer');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('role:viewer');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('role:billing');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->middleware('role:billing');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('role:billing');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('role:viewer');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('role:viewer');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('role:billing');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('role:billing');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('role:billing');
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show'])->middleware('role:viewer');
    Route::post('/invoices/{invoice}/send', InvoiceSendController::class)->middleware('role:billing');

    Route::get('/invoice-items', [InvoiceItemController::class, 'index'])->middleware('role:viewer');
    Route::get('/invoice-items/{invoice_item}', [InvoiceItemController::class, 'show'])->middleware('role:viewer');
    Route::post('/invoice-items', [InvoiceItemController::class, 'store'])->middleware('role:billing');
    Route::put('/invoice-items/{invoice_item}', [InvoiceItemController::class, 'update'])->middleware('role:billing');
    Route::delete('/invoice-items/{invoice_item}', [InvoiceItemController::class, 'destroy'])->middleware('role:billing');

    Route::get('/payments', [PaymentController::class, 'index'])->middleware('role:viewer');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('role:viewer');
    Route::post('/payments', [PaymentController::class, 'store'])->middleware('role:billing');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->middleware('role:billing');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->middleware('role:billing');
    Route::post('/payments/{payment}/checkout', CheckoutController::class)->middleware('role:billing');

    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->middleware('role:viewer');
    Route::get('/reports/invoices-summary', [ReportController::class, 'invoicesSummary'])->middleware('role:viewer');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->middleware('role:billing');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
});
