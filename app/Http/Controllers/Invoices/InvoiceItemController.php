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
        $this->authorize('viewAny', InvoiceItem::class);

        return response()->json(InvoiceItem::query()->latest()->paginate());
    }

    public function store(StoreInvoiceItemRequest $request): JsonResponse
    {
        $this->authorize('create', InvoiceItem::class);

        $item = InvoiceItem::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return response()->json($item, 201);
    }

    public function show(InvoiceItem $invoiceItem): JsonResponse
    {
        $this->authorize('view', $invoiceItem);

        return response()->json($invoiceItem);
    }

    public function update(UpdateInvoiceItemRequest $request, InvoiceItem $invoiceItem): JsonResponse
    {
        $this->authorize('update', $invoiceItem);

        $invoiceItem->update($request->validated());

        return response()->json($invoiceItem->refresh());
    }

    public function destroy(InvoiceItem $invoiceItem): JsonResponse
    {
        $this->authorize('delete', $invoiceItem);

        $invoiceItem->delete();

        return response()->json([], 204);
    }
}
