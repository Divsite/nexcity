<?php

namespace App\Http\Requests\FormSubmissionProcesses;

use App\Models\FormSubmissions\FormSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateFormSubmissionProcessRequest extends FormRequest
{
    public $formSubmission;

    public function authorize(): bool
    {
        $this->formSubmission = FormSubmission::with([
            'currentStatus.status', 'currentStatus.process', 'form.processes'
        ])->findOrFail($this->route('id'));

        return true;
    }

    public function rules(): array
    {
        $statusIds = [];
        $commentRequired = false;

        $currentStatus = $this->formSubmission->currentStatus;

        // Make sure is not revert submitter or ended process
        $proceedNextProcess = !$currentStatus->is_revert_submitter && !$currentStatus->is_end_process;

        if ($currentStatus->status->is_default && empty($currentStatus->process)) {
            $process = $this->formSubmission->form->processes()->where('order', 1)->first();

            if ($process && $process->actions()->exists()) {
                $action = $process->actions()->where('status_id', $this->get('status_id'))->first();

                if ($action && $action->comment_required) {
                    $commentRequired = true;
                }
            }

        } else {
            // Next process
            if ($proceedNextProcess && $currentStatus->process && $currentStatus->status) {
                $action = $currentStatus->process->actions()
                    ->where('status_id', $currentStatus->status->id)
                    ->first();

                // Proceed next process if linked process exists
                if ($action && $action->linkedProcess) {
                    $process = $action->linkedProcess;

                    if ($process->actions()->exists()) {
                        $action = $process->actions()->where('status_id', $this->get('status_id'))->first();

                        if ($action && $action->comment_required) {
                            $commentRequired = true;
                        }
                    }
                }
            }
        }

        // Get status ID
        if (!empty($process->actions)) {
            foreach ($process->actions as $action) {
                $statusIds[] = $action->status->id;
            }
        }

        $actions = null;
        if (!empty($statusIds)) {
            $actions = Rule::in($statusIds);
        }

        return [
            'status_id' => ['required', $actions],
            'comment' => ['nullable', Rule::requiredIf($commentRequired), 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status_id' => Str::lower(__('messages.status')),
            'comment' => Str::lower(__('messages.comment')),
        ];
    }
}
