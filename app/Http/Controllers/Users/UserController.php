<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Roles\Role;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-users')->only('index');
        $this->middleware('permission:read-users')->only('show');
        $this->middleware('permission:edit-users')->only('edit', 'update');
        $this->middleware('permission:add-users')->only('create', 'store');
        $this->middleware('permission:delete-users')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (config('core.custom_user_module_enabled')) {
            return redirect()->route(config('core.user_module_route_name'));
        }

        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (config('core.custom_user_module_enabled')) {
            return redirect()->route(config('core.user_module_route_name'));
        }

        $roles = Role::all()->pluck('display_name', 'name');

        return view('users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        if (config('core.custom_user_module_enabled')) {
            return response()->json(['success' => false, 'message' => __('Forbidden')], 403);
        }

        $validated = $request->validated();

        $model = new User();
        $model->name = $validated['name'];
        $model->username = $validated['username'];
        $model->email = $validated['email'];
        $model->password = Hash::make($validated['password']);

        if ($validated['phone']) {
            $model->phone = phone($validated['phone'], 'MY');
        }

        $model->assignRole($validated['role']);

        $fileName = Str::random(30) . '.png';

        if ($request->hasFile('avatar')) {
            $request->file('avatar')->storeAs(User::AVATAR_PATH, $fileName);
            $model->initial_name = User::AVATAR_NOT_INITIAL_NAME;
        } else {
            Avatar::create($model->name)->save(storage_path('app/' . User::AVATAR_PATH . $fileName), 100);
            $model->initial_name = User::AVATAR_INITIAL_NAME;
        }

        $model->avatar = $fileName;

        $model->save();

        if ($validated['status'] == User::VERIFIED) {
            $model->markEmailAsVerified();
        }

        if ($validated['status'] == User::UNVERIFIED) {
            $model->sendEmailVerificationNotification();
        }

        activity(__('messages.users'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.users_has_been_created', ['name' => $model->name]));

        flash()->success(__('messages.user_successfully_created'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('users.index')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (config('core.custom_user_module_enabled')) {
            return redirect()->route(config('core.user_module_route_name'));
        }

        $model = User::findOrFail($id);

        return view('users.show', [
            'model' => $model
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (config('core.custom_user_module_enabled')) {
            return redirect()->route(config('core.user_module_route_name'));
        }

        $model = User::findOrFail($id);

        $roles = Role::all()->pluck('display_name', 'name');

        $verified = User::UNVERIFIED;
        if ($model->email_verified_at) {
            $verified = User::VERIFIED;
        }

        $userRole = null;
        if ($model->roles && sizeof($model->roles) != 0) {
            $userRole = $model->roles[0]->name;
        }

        return view('users.edit', [
            'model' => $model,
            'roles' => $roles,
            'verified' => $verified,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        if (config('core.custom_user_module_enabled')) {
            return response()->json(['success' => false, 'message' => __('Forbidden')], 403);
        }

        $validated = $request->validated();

        $model = User::findOrFail($id);
        $model->name = $validated['name'];
        $model->username = $validated['username'];
        $model->email = $validated['email'];
        $model->initial_name = $validated['initial_name'];

        if ($validated['phone']) {
            $model->phone = phone($validated['phone'], 'MY');
        }

        if ($validated['password']) {
            $model->password = Hash::make($validated['password']);
        }

        $model->syncRoles($validated['role']);

        if ($request->hasFile('avatar')) {
            Storage::delete(User::AVATAR_PATH . $model->avatar);
            $avatar = $request->file('avatar');
            $fileName = $avatar->hashName();
            $avatar->store(User::AVATAR_PATH);
            $model->avatar = $fileName;
            $model->initial_name = User::AVATAR_NOT_INITIAL_NAME;
        }

        if ($model->initial_name == User::AVATAR_INITIAL_NAME) {
            Storage::delete(User::AVATAR_PATH . $model->avatar);
            $fileName = Str::random(30) . '.png';
            Avatar::create($model->name)->save(storage_path('app/' . User::AVATAR_PATH . $fileName), 100);
            $model->avatar = $fileName;
        }

        if ($validated['status'] == User::VERIFIED) {
            $model->markEmailAsVerified();
        }

        if ($validated['status'] == User::UNVERIFIED) {
            $model->sendEmailVerificationNotification();
            $model->email_verified_at = null;
        }

        $model->update();

        activity(__('messages.users'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.users_has_been_updated', ['name' => $model->name]));

        flash()->success(__('messages.user_successfully_updated'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('users.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (config('core.custom_user_module_enabled')) {
            return response()->json(['success' => false, 'message' => __('Forbidden')], 403);
        }

        $model = User::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.user_successfully_deleted'));

            activity(__('messages.users'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.users_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('users.index')]);
    }
}
