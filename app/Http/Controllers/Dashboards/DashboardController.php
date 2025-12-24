<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Permissions\Permission;
use App\Models\Roles\Role;
use App\Models\Users\User;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $total = null;

        if (auth()->user()->can('browse-users') && auth()->user()->can('browse-roles') && auth()->user()->can('browse-permissions')) {
            $user = User::count();
            $role = Role::count();
            $permission = Permission::count();

            $total = collect([
                'user' => $user,
                'role' => $role,
                'permission' => $permission,
            ]);
        }

        return view('dashboard', [
            'total' => $total
        ]);
    }
}
