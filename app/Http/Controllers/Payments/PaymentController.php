<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        return response()->json(Payment::query()->latest()->paginate());
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = Payment::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
            'provider' => $request->validated('provider', 'manual'),
            'status' => $request->validated('status', 'pending'),
        ]);

        return response()->json($payment, 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return response()->json($payment->load(['invoice']));
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $payment->update($request->all());

        return response()->json($payment->refresh());
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        return response()->json([], 204);
    }
}
