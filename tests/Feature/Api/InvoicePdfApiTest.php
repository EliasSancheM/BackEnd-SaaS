<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePdfApiTest extends TestCase
{
    public function test_invoice_pdf_returns_pdf_response(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->get('/api/invoices/'.$invoice->id.'/pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
