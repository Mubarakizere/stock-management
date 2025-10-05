<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Collect the user's role names using your existing schema
        $roles = [];

        // If you have roles + role_user pivot tables
        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $roles = DB::table('roles')
                ->join('role_user', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
        }
        // Fallback: plain column on users table (users.role)
        elseif (Schema::hasColumn('users', 'role') && !empty($user->role)) {
            $roles = [$user->role];
        }

        $has = fn (string $name) => in_array($name, $roles, true);

        if ($has('admin'))   return redirect()->route('dashboard');
        if ($has('manager')) return redirect()->route('transactions.index');
        if ($has('cashier')) return redirect()->route('sales.index');

        // default
        return redirect()->route('dashboard');
    }
}
