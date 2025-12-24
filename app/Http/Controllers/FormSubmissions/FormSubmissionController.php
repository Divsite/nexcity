<?php

namespace App\Http\Controllers\FormSubmissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSubmissions\CreateFormSubmissionRequest;
use App\Http\Requests\FormSubmissions\UpdateFormSubmissionRequest;
use App\Jobs\FormSubmissions\StoreFormSubmissionJob;
use App\Jobs\FormSubmissions\UpdateFormSubmissionJob;
use App\Models\Forms\Form;
use App\Models\FormSubmissions\FormSubmission;
use Illuminate\Support\Facades\App;

class FormSubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-submissions')->only('index');
        $this->middleware('permission:read-submissions')->only('show');
        $this->middleware('permission:edit-submissions')->only('edit', 'update');
        $this->middleware('permission:add-submissions')->only('create', 'store');
        $this->middleware('permission:delete-submissions')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index($form)
    {
        $model = Form::findOrFail($form);

        return view('form-submissions.index', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($form)
    {
        $model = Form::findOrFail($form);

        return view('form-submissions/create-edit', [
            'submission' => false,
            'model' => $model,
            'formData' => $model->prepare_input,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFormSubmissionRequest $request)
    {
        if ($request->isLastPage) {
            StoreFormSubmissionJob::dispatch($request->form, $request->validated(), auth()->user(),
                App::currentLocale());
        }

        // Redirect
        return response()->json([
            'success' => true,
            'current_page_index' => $request->current_page_index,
            'is_last_page' => $request->isLastPage
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $model = FormSubmission::with(['form', 'files'])
            ->findOrFail($id);

        return view('form-submissions/show', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $model = FormSubmission::with('form')->findOrFail($id);

        $updateRouteName = "submissions.update";

        return view('form-submissions/create-edit', [
            'submission' => true,
            'model' => $model,
            'formData' => $model->prepareEditInput(),
            'updateRouteName' => $updateRouteName,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormSubmissionRequest $request)
    {
        if ($request->isLastPage) {
            UpdateFormSubmissionJob::dispatch($request->formSubmission, $request->validated(), auth()->user(),
                App::currentLocale());
        }

        // Redirect
        return response()->json([
            'success' => true,
            'current_page_index' => $request->current_page_index,
            'is_last_page' => $request->isLastPage
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $model = FormSubmission::with('form')->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.submission_successfully_deleted'));

            activity(__('messages.form_submissions'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.form_submissions_has_been_deleted', ['id' => $model->id]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('forms.submissions.index', $model->form->id)]);
    }
}
