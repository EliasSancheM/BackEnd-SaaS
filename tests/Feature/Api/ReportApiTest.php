<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    public function test_revenue_returns_total(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/reports/revenue');

        $response->assertOk()->assertJsonStructure(['total']);
    }

    public function test_invoices_summary_returns_counts(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/reports/invoices-summary');

        $response->assertOk()->assertJsonStructure(['draft', 'sent', 'paid', 'overdue', 'cancelled']);
    }

    public function test_export_csv_returns_placeholder(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/reports/export/csv');

        $response->assertOk()->assertJsonPath('message', 'Export CSV not implemented yet.');
    }
}
