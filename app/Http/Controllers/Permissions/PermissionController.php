<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Permissions\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-permissions')->only('index');
        $this->middleware('permission:read-permissions')->only('show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('permissions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Permission::findOrFail($id);
        $roles = $model->roles;

        return view('permissions.show', [
            'model' => $model,
            'roles' => $roles,
        ]);
    }
}
