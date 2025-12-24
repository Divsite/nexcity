<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\CreateFormRequest;
use App\Http\Requests\Forms\UpdateFormRequest;
use App\Models\Forms\Form;
use App\Models\FormTypes\FormType;
use Cknow\Money\Money;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-forms')->only('index');
        $this->middleware('permission:read-forms')->only('show');
        $this->middleware('permission:edit-forms')->only('edit', 'update');
        $this->middleware('permission:add-forms')->only('create', 'store');
        $this->middleware('permission:delete-forms')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('forms.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $formTypes = FormType::select(['id', 'name'])->get();

        $currencies = Money::getISOCurrencies();

        return view('forms.create-edit', [
            'formTypes' => $formTypes,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFormRequest $request)
    {
        $validated = $request->validated();

        $model = new Form();
        $model->name = $validated['name'];
        $model->type_id = $validated['type_id'];
        $model->properties = !empty($validated['properties']) ? $validated['properties'] : null;
        $model->webhook_url = $validated['webhook_url'];
        $model->use_current_url = $validated['use_current_url'];

        $model->save();

        // Save default status in `form_process_statuses` table
        $model->defaultStatus()->create([
            'name' => null,
            'status' => true,
            'is_default' => true,
        ]);

        activity(__('messages.forms'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.forms_has_been_created', ['name' => $model->name]));

        flash()->success(__('messages.form_successfully_created'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('forms.index')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = Form::with('type')->findOrFail($id);

        return view('forms.show', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Form::findOrFail($id);

        $formTypes = FormType::select(['id', 'name'])->get();

        $currencies = Money::getISOCurrencies();

        return view('forms.create-edit', [
            'model' => $model,
            'formTypes' => $formTypes,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormRequest $request, string $id)
    {
        $validated = $request->validated();

        $model = Form::findOrFail($id);
        $model->name = $validated['name'];
        $model->type_id = $validated['type_id'];
        $model->properties = !empty($validated['properties']) ? $validated['properties'] : null;
        $model->webhook_url = $validated['webhook_url'];
        $model->use_current_url = $validated['use_current_url'];

        $model->save();

        activity(__('messages.forms'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.forms_has_been_updated', ['name' => $model->name]));

        flash()->success(__('messages.form_successfully_updated'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('forms.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = Form::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.user_successfully_deleted'));

            activity(__('messages.forms'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.forms_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('forms.index')]);
    }
}
