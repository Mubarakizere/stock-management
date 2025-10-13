<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // 🧱 If user not logged in
        if (!$user) {
            abort(401, 'You must be logged in.');
        }

        $userRoles = [];

        // 🧱 Only check if tables exist
        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $userRoles = DB::table('roles')
                ->join('role_user', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
        }

        // 🧱 Fallback for users without assigned roles
        if (empty($userRoles)) {
            abort(403, 'Access denied: user has no assigned roles.');
        }

        // 🧱 Normalize to lowercase for consistent comparison
        $userRoles = array_map('strtolower', $userRoles);
        $roles = array_map('strtolower', $roles);

        // 🧱 Compare roles
        if (empty(array_intersect($roles, $userRoles))) {
            abort(403, 'Access denied: insufficient permissions.');
        }

        return $next($request);
    }
}
