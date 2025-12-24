<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\AddFieldRequest;
use App\Http\Requests\Forms\EditFieldRequest;
use App\Service\Forms\FormCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BuilderController extends Controller
{
    public FormCleaner $formCleaner;

    public function __construct()
    {
        $this->formCleaner = new FormCleaner();
    }

    public function addField(AddFieldRequest $request)
    {
        $validated = $request->validated();

        $properties = form_properties($validated['type']);

        if (!$properties) {
            $message = __('messages.properties_not_exist');

            if (app()->environment() == 'production') {
                $message = __('messages.something_went_wrong');
            }

            return response()->json(['status' => false, 'message' => $message], 422);
        }

        return response()->json(['status' => true, 'generate_id' => Str::uuid(), 'properties' => $properties]);
    }

    public function editField(EditFieldRequest $request)
    {
        $validated = $request->validated();

        $properties = form_properties($validated['type']);

        if (!$properties) {
            $message = __('messages.properties_not_exist');

            if (app()->environment() == 'production') {
                $message = __('messages.something_went_wrong');
            }

            return response()->json(['status' => false, 'message' => $message], 422);
        }

        return response()->json(['status' => true, 'properties' => $this->formCleaner->getData($validated)]);
    }
}
