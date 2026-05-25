<?php

namespace App\Jobs;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function handle(): void
    {
        if (! $this->invoice->client?->email) {
            return;
        }

        Mail::to($this->invoice->client->email)
            ->send(new InvoiceEmail($this->invoice));

        $this->invoice->updateQuietly([
            'sent_at' => now(),
        ]);
    }
}
