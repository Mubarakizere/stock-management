<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $roles = DB::table('roles')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->pluck('roles.name')
            ->toArray();

        if (in_array('admin', $roles, true))   return redirect()->route('dashboard');
        if (in_array('manager', $roles, true)) return redirect()->route('transactions.index');
        if (in_array('cashier', $roles, true)) return redirect()->route('sales.index');

        return redirect()->route('dashboard');
    }
}
