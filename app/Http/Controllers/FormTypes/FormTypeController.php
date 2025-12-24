<?php

namespace App\Http\Controllers\FormTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormTypes\CreateFormTypeRequest;
use App\Http\Requests\FormTypes\UpdateFormTypeRequest;
use App\Models\FormTypes\FormType;

class FormTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-form-types')->only('index');
        $this->middleware('permission:read-form-types')->only('show');
        $this->middleware('permission:edit-form-types')->only('edit', 'update');
        $this->middleware('permission:add-form-types')->only('add', 'store');
        $this->middleware('permission:delete-form-types')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('form-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('form-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFormTypeRequest $request)
    {
        $validated = $request->validated();

        $model = new FormType();
        $model->name = $validated['name'];
        $model->description = $validated['description'];
        $model->save();

        flash()->success(__('messages.form_type_successfully_created'));

        activity(__('messages.form_types'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.form_types_has_been_created', ['name' => $model->name]));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('form-types.index')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = FormType::findOrFail($id);

        return view('form-types.show', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = FormType::findOrFail($id);

        return view('form-types.edit', [
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormTypeRequest $request, string $id)
    {
        // Retrieve the validated input data...
        $validated = $request->validated();

        $model = FormType::findOrFail($id);
        $model->name = $validated['name'];
        $model->description = $validated['description'];
        $model->update();

        flash()->success(__('messages.form_type_successfully_updated'));

        activity(__('messages.form_types'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.form_types_has_been_updated', ['name' => $model->name]));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('form-types.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $model = FormType::query()->find($id);

        if ($model) {
            $model->delete();

            flash()->success(__('messages.form_type_successfully_deleted'));

            activity(__('messages.form_types'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.form_types_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('form-types.index')]);
    }
}
