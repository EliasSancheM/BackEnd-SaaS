<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // Configuración de empresa/emisor (giro, datos SII, preferencias de
            // facturación, apariencia). Blob flexible; se promoverán columnas
            // estructuradas cuando se implemente la integración SII real.
            $table->json('settings')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
};
