<?php

namespace App\Http\Requests\FormProcesses;

use App\Models\FormProcesses\FormProcess;
use App\Models\Permissions\Permission;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFormProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = FormProcess::query()->findOrFail($this->get('id'));

        $activeStatuses = $model->form->statuses()
            ->where('status', true)
            ->pluck('id');

        $activeProcesses = $model->form->processes()
            ->where('id', '!=', $this->get('id'))
            ->where('status', true)
            ->pluck('id')
            ->prepend(FormProcess::REVERT_SUBMITTER) // push to first element
            ->push(FormProcess::END_PROCESS); // push to last element

        $processorUsers = User::permission('process-submissions')->pluck('id');

        // Get the permission
        $permission = Permission::where('name', 'process-submissions')->firstOrFail();
        $processorRoles = $permission->roles()->pluck('id');

        // User IDs
        $allUserIds = collect($this->get('processor_users'));

        // Role User IDs
        $userRoles = User::query()->whereHas('roles', function (Builder $query) {
            $query->whereIn('id', $this->get('processor_roles'));
        })->pluck('id');

        // Combine all IDs and remove duplicate
        $managerIds = $allUserIds->merge($userRoles)->unique();

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'actions' => ['required', 'array'],
            'actions.*.id' => ['nullable'],
            'actions.*.status_id' => ['required', 'distinct', Rule::in($activeStatuses)],
            'actions.*.linked_process_id' => ['required', Rule::in($activeProcesses)],
            'actions.*.comment_required' => ['required', 'boolean'],
            'processor_users' => ['nullable', 'array', Rule::in($processorUsers)],
            'processor_roles' => ['nullable', 'array', Rule::in($processorRoles)],
            'decision_type' => ['required', Rule::in(FormProcess::decisionTypeList()->keys())],
            'majority_percentage' => [
                'nullable',
                'numeric',
                Rule::requiredIf($this->get('decision_type') == FormProcess::DECISION_TYPE_MAJORITY),
                'between:0,100',
            ],
            'manager_id' => [
                'nullable',
                Rule::requiredIf(in_array($this->get('decision_type'),
                    [FormProcess::DECISION_TYPE_MAJORITY, FormProcess::DECISION_TYPE_ALL])),
                Rule::in($managerIds),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'status' => Str::lower(__('messages.status')),
            'actions' => Str::lower(__('messages.status')),
            'actions.*.id' => Str::lower(__('messages.id')),
            'actions.*.status_id' => Str::lower(__('messages.status')),
            'actions.*.linked_process_id' => Str::lower(__('messages.process', ['number' => ''])),
            'actions.*.comment_required' => Str::lower(__('messages.comment')),
            'processor_users' => Str::lower(__('messages.processor_users')),
            'processor_roles' => Str::lower(__('messages.processor_roles')),
            'decision_type' => Str::lower(__('messages.decision_maker')),
            'majority_percentage' => Str::lower(__('messages.percentage')),
            'manager_id' => Str::lower(__('messages.person_in_charge')),
        ];
    }
}
