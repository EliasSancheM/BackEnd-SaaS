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
        return response()->json(Client::query()->latest()->paginate());
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$request->validated(),
        ]);

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($request->only(['name', 'rut', 'email', 'phone', 'address', 'city', 'notes']));

        return response()->json($client->refresh());
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([], 204);
    }
}
