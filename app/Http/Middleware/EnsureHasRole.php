<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasRole
{
    private const array HIERARCHY = [
        'viewer' => ['viewer', 'billing', 'admin', 'owner'],
        'billing' => ['billing', 'admin', 'owner'],
        'admin' => ['admin', 'owner'],
        'owner' => ['owner'],
    ];

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $allowed = self::HIERARCHY[$role] ?? [$role];

        setPermissionsTeamId($user->tenant_id);

        if (! $user->hasAnyRole($allowed)) {
            return response()->json(['message' => 'Forbidden: insufficient role'], 403);
        }

        return $next($request);
    }
}
