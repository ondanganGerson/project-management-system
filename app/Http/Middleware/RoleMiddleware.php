<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Restricts route access based on the authenticated user's role.
 *
 * Usage in routes: ->middleware('role:admin')
 *                  ->middleware('role:admin,manager')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Please log in.',
                'data'    => null,
            ], 401);
        }

        if (! $user->hasRole($roles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to perform this action.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}
