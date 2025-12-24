<?php

namespace App\Http\Requests\FormSubmissionFiles;

use App\Models\Forms\Form;
use Illuminate\Foundation\Http\FormRequest;

class CreateTemporaryFileRequest extends FormRequest
{
    public Form $form;
    public array $formField;

    public function authorize(): bool
    {
        $this->form = Form::findOrFail($this->route('id'));
        $this->formField = form_fields();

        return true;
    }

    public function rules(): array
    {
        $rules = [];

        if ($this->form->properties) {
            foreach ($this->form->prepare_fields as $property) {

                if (
                    $property['type'] == $this->formField['file'] &&
                    $property['id'] == $this->route('uuid')
                ) {
                    if ($property['accept_files']) {
                        $rules['filepond'] = [
                            'required',
                            'file',
                            "mimetypes:{$property['accept_files']}",
                            "max:".$property['max_file_size'] * 1024
                        ];
                    } else {
                        $rules['filepond'] = ['required', 'file', "max:".$property['max_file_size'] * 1024];
                    }
                }

            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'filepond.mimetypes' => __('messages.file_of_invalid_type'),
            'filepond.max' => __('messages.file_is_too_large'),
        ];
    }
}
