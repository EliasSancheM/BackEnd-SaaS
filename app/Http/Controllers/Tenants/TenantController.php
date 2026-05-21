<?php

namespace App\Http\Controllers\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(request()->user()?->tenant);
    }

    public function index(): JsonResponse
    {
        return response()->json(Tenant::query()->latest()->get());
    }
}
