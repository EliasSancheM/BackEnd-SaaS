<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceSendController extends Controller
{
    public function __invoke(Invoice $invoice): JsonResponse
    {
        $this->authorize('send', $invoice);
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be sent'], 422);
        }

        if (! $invoice->client?->email) {
            return response()->json(['message' => 'Client has no email address'], 422);
        }

        $invoice->update(['status' => 'sent', 'sent_at' => now()]);

        SendInvoiceEmail::dispatch($invoice);

        return response()->json([
            'message' => 'Invoice sent successfully',
            'invoice' => $invoice->fresh()->load(['client', 'items']),
        ]);
    }
}
