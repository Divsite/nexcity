<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\CreateRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Permissions\Permission;
use App\Models\Roles\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-roles')->only('index');
        $this->middleware('permission:read-roles')->only('show');
        $this->middleware('permission:edit-roles')->only('edit', 'update');
        $this->middleware('permission:add-roles')->only('add', 'store');
        $this->middleware('permission:delete-roles')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissionByGroup = Permission::query()
            ->orderBy('group')
            ->get()
            ->groupBy('group');

        return view('roles.create', [
            'permissionByGroup' => $permissionByGroup,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRoleRequest $request)
    {
        $validated = $request->validated();

        $model = new Role();
        $model->name = $validated['name'];
        $model->display_name = $validated['display_name'];
        $model->description = $validated['description'];
        $model->save();

        if (!empty($validated['permissions'])) {
            $items = [];
            foreach ($validated['permissions'] as $permission) {
                $value = Permission::find($permission);
                if ($value) {
                    $items[] = $value->id;
                }
            }

            $model->syncPermissions($items);
        }

        flash()->success(__('messages.role_successfully_created'));

        activity(__('messages.roles'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.roles_has_been_created', ['name' => $model->name]));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('settings.roles.index')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Role::findOrFail($id);

        return view('roles.show', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Role::findOrFail($id);
        $rolePermission = $model->permissions->pluck('id');

        $permissionByGroup = Permission::query()
            ->orderBy('group')
            ->get()
            ->groupBy('group');

        return view('roles.edit', [
            'model' => $model,
            'permissionByGroup' => $permissionByGroup,
            'rolePermission' => $rolePermission,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        // Retrieve the validated input data...
        $validated = $request->validated();

        $model = Role::findOrFail($id);
        $model->display_name = $validated['display_name'];
        $model->description = $validated['description'];
        $model->update();

        if (!empty($validated['permissions'])) {
            $items = [];
            foreach ($validated['permissions'] as $permission) {
                $value = Permission::find($permission);
                if ($value) {
                    $items[] = $value->id;
                }
            }

            $model->syncPermissions($items);
        } else {
            $model->syncPermissions([]);
        }

        flash()->success(__('messages.role_successfully_updated'));

        activity(__('messages.roles'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.roles_has_been_updated', ['name' => $model->name]));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('settings.roles.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $model = Role::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.role_successfully_deleted'));

            activity(__('messages.roles'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.roles_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('settings.roles.index')]);
    }
}
