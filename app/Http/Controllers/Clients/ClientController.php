<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        return response()->json(Client::query()->latest()->paginate());
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $client = Client::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return response()->json($client);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update($request->only(['name', 'rut', 'email', 'phone', 'address', 'city', 'notes']));

        return response()->json($client->refresh());
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        if ($client->invoices()->exists()) {
            return response()->json(['message' => 'El cliente tiene facturas asociadas.'], 409);
        }

        $client->delete();

        return response()->json([], 204);
    }
}
