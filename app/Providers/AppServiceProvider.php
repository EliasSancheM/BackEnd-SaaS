<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Policies\ClientPolicy;
use App\Policies\InvoiceItemPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(InvoiceItem::class, InvoiceItemPolicy::class);
    }
}
