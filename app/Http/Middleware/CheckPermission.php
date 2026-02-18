<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        // Support comma-separated permissions in a single argument
        $allPermissions = [];
        foreach ($permissions as $permission) {
            $allPermissions = array_merge($allPermissions, explode(',', $permission));
        }
        $allPermissions = array_map('trim', $allPermissions);

        // Check if user has ANY of the required permissions (OR logic)
        $hasPermission = false;
        foreach ($allPermissions as $permission) {
            if ($request->user()->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            $message = "You do not have any of the required permissions: " . implode(', ', $allPermissions);
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            abort(403, $message);
        }

        return $next($request);
    }
}
