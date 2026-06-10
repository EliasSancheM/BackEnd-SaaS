<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // IVA por defecto en Chile: 19%. Se deriva tax_total = subtotal * tax_rate / 100.
            $table->decimal('tax_rate', 5, 2)->default(19)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('tax_rate');
        });
    }
};
