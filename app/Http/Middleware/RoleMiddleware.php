<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // If not logged in, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // If the user's role is not allowed, block access
        if (!in_array($user->role, $roles)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
