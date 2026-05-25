<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function revenue(): JsonResponse
    {
        return response()->json([
            'total' => (float) Invoice::query()->sum('total'),
        ]);
    }

    public function invoicesSummary(): JsonResponse
    {
        return response()->json([
            'draft' => Invoice::query()->where('status', 'draft')->count(),
            'sent' => Invoice::query()->where('status', 'sent')->count(),
            'paid' => Invoice::query()->where('status', 'paid')->count(),
            'overdue' => Invoice::query()->where('status', 'overdue')->count(),
            'cancelled' => Invoice::query()->where('status', 'cancelled')->count(),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = Invoice::query()->get(['number', 'status', 'total']);

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Número', 'Estado', 'Total']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row->number, $row->status, $row->total]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'facturas.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
