<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantResolver
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            app()->instance('current_tenant', $request->user()->tenant);
        }

        return $next($request);
    }
}
