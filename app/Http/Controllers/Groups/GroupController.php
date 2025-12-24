<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\CreateGroupRequest;
use App\Http\Requests\Groups\UpdateGroupRequest;
use App\Models\Groups\Group;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-groups')->only('index');
        $this->middleware('permission:read-groups')->only('show');
        $this->middleware('permission:edit-groups')->only('edit', 'update');
        $this->middleware('permission:add-groups')->only('create', 'store');
        $this->middleware('permission:delete-groups')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateGroupRequest $request)
    {
        $validated = $request->validated();

        $model = new Group();
        $model->name = $validated['name'];
        $model->slug = Str::slug($validated['name']);
        $model->description = $validated['description'];
        $model->save();

        activity(__('messages.groups'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.groups_has_been_created', ['name' => $model->name]));

        flash()->success(__('messages.group_successfully_created'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('groups.index')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Group::findOrFail($id);

        return view('groups.show', [
            'model' => $model
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Group::findOrFail($id);

        return view('groups.edit', [
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGroupRequest $request, string $id)
    {
        $validated = $request->validated();

        $model = Group::findOrFail($id);
        $model->name = $validated['name'];
        $model->slug = Str::slug($validated['name']);
        $model->description = $validated['description'];

        $model->update();

        activity(__('messages.groups'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.groups_has_been_updated', ['name' => $model->name]));

        flash()->success(__('messages.group_successfully_updated'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('groups.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = Group::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.group_successfully_deleted'));

            activity(__('messages.groups'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.groups_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('groups.index')]);
    }
}
