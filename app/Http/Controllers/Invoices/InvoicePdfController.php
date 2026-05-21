<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice->load(['client', 'items', 'tenant']),
        ]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
