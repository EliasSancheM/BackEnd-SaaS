<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceItemRequest;
use App\Http\Requests\Invoices\UpdateInvoiceItemRequest;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;

class InvoiceItemController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(InvoiceItem::query()->latest()->paginate());
    }

    public function store(StoreInvoiceItemRequest $request): JsonResponse
    {
        $item = InvoiceItem::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return response()->json($item, 201);
    }

    public function show(InvoiceItem $invoiceItem): JsonResponse
    {
        return response()->json($invoiceItem);
    }

    public function update(UpdateInvoiceItemRequest $request, InvoiceItem $invoiceItem): JsonResponse
    {
        $invoiceItem->update($request->validated());

        return response()->json($invoiceItem->refresh());
    }

    public function destroy(InvoiceItem $invoiceItem): JsonResponse
    {
        $invoiceItem->delete();

        return response()->json([], 204);
    }
}
