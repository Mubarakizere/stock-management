<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) abort(401);

        $userRoles = [];

        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $userRoles = DB::table('roles')
                ->join('role_user', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
        }

        if (empty(array_intersect($roles, $userRoles))) abort(403);

        return $next($request);
    }
}
