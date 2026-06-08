<?php

namespace App\Http\Controllers\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\UpdateTenantRequest;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(request()->user()?->tenant);
    }

    public function update(UpdateTenantRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $validated = $request->validated();

        if (array_key_exists('settings', $validated)) {
            // Merge sobre lo existente para permitir guardados parciales.
            $tenant->settings = array_merge((array) $tenant->settings, $validated['settings']);

            // Mantener tenants.name sincronizado con la razón social.
            if (! empty($validated['settings']['companyName'])) {
                $tenant->name = $validated['settings']['companyName'];
            }
        }

        if (! empty($validated['name'])) {
            $tenant->name = $validated['name'];
        }

        $tenant->save();

        return response()->json($tenant->refresh());
    }
}
