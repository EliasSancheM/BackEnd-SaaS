<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Invoices\InvoiceController;
use App\Http\Controllers\Invoices\InvoiceItemController;
use App\Http\Controllers\Invoices\InvoicePdfController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Tenants\TenantController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('invoice-items', InvoiceItemController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show']);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/invoices-summary', [ReportController::class, 'invoicesSummary']);
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv']);
});
