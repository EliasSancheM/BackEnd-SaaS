<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_mp_payment_id_unique');
            $table->renameColumn('mp_payment_id', 'provider_payment_id');
            $table->string('paypal_order_id')->nullable()->after('provider_payment_id');
            $table->string('paypal_payer_id')->nullable()->after('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['paypal_payer_id', 'paypal_order_id']);
            $table->renameColumn('provider_payment_id', 'mp_payment_id');
            $table->unique('mp_payment_id');
        });
    }
};
