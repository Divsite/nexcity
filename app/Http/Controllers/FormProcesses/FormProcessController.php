<?php

namespace App\Http\Controllers\FormProcesses;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormProcesses\CreateFormProcessRequest;
use App\Http\Requests\FormProcesses\CreateFormProcessStatusRequest;
use App\Http\Requests\FormProcesses\UpdateFormDefaultStatusRequest;
use App\Http\Requests\FormProcesses\UpdateFormProcessRequest;
use App\Http\Requests\FormProcesses\UpdateFormProcessStatusRequest;
use App\Http\Resources\FormProcesses\ProcessorUserCollection;
use App\Jobs\FormProcesses\UpdateFormSubmissionProcessorJob;
use App\Models\FormProcesses\FormProcess;
use App\Models\FormProcesses\FormProcessAction;
use App\Models\FormProcesses\FormProcessStatus;
use App\Models\Forms\Form;
use App\Models\Permissions\Permission;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormProcessController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-workflow-processes');
    }

    public function index($id): View
    {
        $model = Form::findOrFail($id);

        $statuses = $this->statuses($model);

        $processes = $this->processes($model);

        // Get the permission
        $permission = Permission::where('name', 'process-submissions')->firstOrFail();
        $roles = $permission->roles;

        return view('form-processes.index', [
            'model' => $model,
            'statuses' => $statuses,
            'processes' => $processes,
            'roles' => $roles,
        ]);
    }

    public function updateDefaultStatus(UpdateFormDefaultStatusRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $model = Form::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        if ($model->defaultStatus()->exists()) {
            $model->defaultStatus()->update([
                'name' => $validated['default_submission_status']
            ]);
        } else {
            $model->defaultStatus()->create([
                'name' => $validated['default_submission_status'],
                'status' => true,
                'is_default' => true,
            ]);
        }

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.default_submission_status_has_been_updated', ['formName' => $model->name]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.default_submission_status_successfully_updated')
        ]);
    }

    public function getStatutes($id): JsonResponse
    {
        $model = Form::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        $statuses = $this->statuses($model);

        return response()->json(['status' => true, 'items' => $statuses]);
    }

    public function storeStatus(CreateFormProcessStatusRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $form = Form::with('statuses')->find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        $model = new FormProcessStatus();
        $model->form_id = $form->id;
        $model->name = $validated['name'];
        $model->status = $validated['status'];

        $model->save();

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_status_has_been_created', [
                'statusName' => $model->name,
                'formName' => $form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.status_successfully_created')
        ]);
    }

    public function updateStatus(UpdateFormProcessStatusRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $model = FormProcessStatus::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.status_not_found')
            ], 404);
        }

        $model->name = $validated['name'];
        $model->status = $validated['status'];

        $model->update();

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_status_has_been_updated', [
                'statusName' => $model->name,
                'formName' => $model->form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.status_successfully_updated')
        ]);
    }

    public function destroyStatus($id): JsonResponse
    {
        $model = FormProcessStatus::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.status_not_found')
            ], 404);
        }

        // If processes exists
        if ($model->form->processes()->exists()) {
            $processes = $model->form->processes;
            foreach ($processes as $process) {
                // Find action that has this status
                $action = $process->actions()->where('status_id', $model->id)->first();

                // If action is found
                if ($action) {
                    // Delete the action
                    $action->delete();
                }
            }
        }

        if ($model->form->submissions()->exists()) {
            $model->delete();
        } else {
            $model->forceDelete();
        }

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_status_has_been_deleted', [
                'statusName' => $model->name,
                'formName' => $model->form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.status_successfully_deleted')
        ]);
    }

    public function getProcesses($id): JsonResponse
    {
        $model = Form::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        $processes = $this->processes($model);

        return response()->json(['status' => true, 'items' => $processes]);
    }

    public function storeProcess(CreateFormProcessRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $form = Form::with('statuses')->find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        $order = 1;
        if ($form->processes) {
            $latestOrder = $form->processes()->orderBy('order', 'desc')->first();
            if ($latestOrder) {
                $order = $latestOrder->order + 1;
            }
        }

        $model = new FormProcess();
        $model->form_id = $form->id;
        $model->name = $validated['name'];
        $model->status = $validated['status'];
        $model->order = $order;
        $model->save();

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_has_been_created', [
                'processName' => $model->name,
                'formName' => $form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.process_successfully_created')
        ]);
    }

    public function updateProcess(UpdateFormProcessRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $model = FormProcess::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.process_not_found')
            ], 404);
        }

        $model->name = $validated['name'];
        $model->status = $validated['status'];
        $model->decision_type = $validated['decision_type'];
        $model->manager_id = $validated['manager_id'];

        if ($model->decision_type == FormProcess::DECISION_TYPE_MAJORITY && !empty($validated['majority_percentage'])) {
            $model->majority_percentage = $validated['majority_percentage'];
        } else {
            $model->majority_percentage = null;
        }

        $model->update();

        $actionIds = [];

        if (!empty($validated['actions'])) {
            foreach ($validated['actions'] as $item) {
                if (!empty($item['id'])) {
                    $action = FormProcessAction::query()->find($item['id']);

                    if ($action) {
                        $action->status_id = $item['status_id'];
                        $action->comment_required = $item['comment_required'];

                        if (in_array($item['linked_process_id'],
                            [FormProcess::REVERT_SUBMITTER, FormProcess::END_PROCESS])) {
                            $action->linked_process_id = null;

                            if ($item['linked_process_id'] == FormProcess::REVERT_SUBMITTER) {
                                $action->is_revert_submitter = true;
                                $action->is_end_process = false;
                            }

                            if ($item['linked_process_id'] == FormProcess::END_PROCESS) {
                                $action->is_revert_submitter = false;
                                $action->is_end_process = true;
                            }
                        } else {
                            $action->is_revert_submitter = false;
                            $action->is_end_process = false;
                            $action->linked_process_id = $item['linked_process_id'];
                        }

                        $action->update();
                    }
                } else {
                    $action = new FormProcessAction();
                    $action->process_id = $model->id;
                    $action->status_id = $item['status_id'];
                    $action->comment_required = $item['comment_required'];

                    if (in_array($item['linked_process_id'],
                        [FormProcess::REVERT_SUBMITTER, FormProcess::END_PROCESS])) {
                        $action->linked_process_id = null;

                        if ($item['linked_process_id'] == FormProcess::REVERT_SUBMITTER) {
                            $action->is_revert_submitter = true;
                            $action->is_end_process = false;
                        }

                        if ($item['linked_process_id'] == FormProcess::END_PROCESS) {
                            $action->is_revert_submitter = false;
                            $action->is_end_process = true;
                        }
                    } else {
                        $action->is_revert_submitter = false;
                        $action->is_end_process = false;
                        $action->linked_process_id = $item['linked_process_id'];
                    }

                    $action->save();

                    $actionIds[] = $action->id;
                }
            }

            $actions = collect($validated['actions'])->pluck('id')->filter();

            if (!empty($actionIds)) {
                foreach ($actionIds as $id) {
                    $actions->push($id);
                }
            }

            $model->actions()->whereNotIn('id', $actions)->delete();
        } else {
            $model->actions()->delete();
        }

        if (!empty($validated['processor_users'])) {
            foreach ($validated['processor_users'] as $user) {
                $processorUser = $model->processorUsers()->where('user_id', $user)->exists();

                if (!$processorUser) {
                    $model->processorUsers()->create([
                        'user_id' => $user,
                    ]);
                }
            }

            // Delete if not in current processor users
            $model->processorUsers()->whereNotIn('user_id', $validated['processor_users'])->delete();
        } else {
            $model->processorUsers()->delete();
        }

        if (!empty($validated['processor_roles'])) {
            foreach ($validated['processor_roles'] as $role) {
                $processorRole = $model->processorRoles()->where('role_id', $role)->exists();

                if (!$processorRole) {
                    $model->processorRoles()->create([
                        'role_id' => $role,
                    ]);
                }
            }

            // Delete if not in current processor roles
            $model->processorRoles()->whereNotIn('role_id', $validated['processor_roles'])->delete();
        } else {
            $model->processorRoles()->delete();
        }

        UpdateFormSubmissionProcessorJob::dispatch($model);

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_has_been_updated', [
                'processName' => $model->name,
                'formName' => $model->form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.process_successfully_updated')
        ]);
    }

    public function destroyProcess($id): JsonResponse
    {
        $model = FormProcess::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.process_not_found')
            ], 404);
        }

        // If processes exists
        if ($model->form->processes()->exists()) {
            $processes = $model->form->processes;
            foreach ($processes as $process) {
                // Find action that has this process
                $action = $process->actions()->where('linked_process_id', $model->id)->first();

                // If action is found
                if ($action) {
                    // Delete the action
                    $action->delete();
                }
            }
        }

        if ($model->form->submissions()->exists()) {
            // Delete current tasks if this process involved
            $submissions = $model->form->submissions;
            foreach ($submissions as $submission) {
                $submission->currentTasks()->where('process_id', $model->id)->delete();
            }

            // Update the sort order to 0
            $model->order = 0;
            $model->update();

            $model->delete();
        } else {
            $model->forceDelete();
        }

        activity(__('messages.workflow_processes'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.workflow_process_has_been_deleted', [
                'processName' => $model->name,
                'formName' => $model->form->name
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.process_successfully_deleted')
        ]);
    }

    public function updateProcessSort(Request $request, $id): JsonResponse
    {
        $model = Form::query()->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.form_not_found')
            ], 404);
        }

        $processes = $model->processes()->get();

        if (empty($processes)) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.process_not_found')
            ], 404);
        }

        foreach ($processes as $process) {
            $process->timestamps = false;
            $id = $process->id;

            foreach ($request->processes as $item) {
                if ($item['id'] == $id) {
                    $process->update(['order' => $item['order']]);
                }
            }
        }

        $processes = $this->processes($model);

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.process_successfully_sorted'),
            'items' => $processes,
        ]);
    }

    public function getProcessorUsers(Request $request): ProcessorUserCollection
    {
        // Start the query with the permission filter
        $query = User::permission('process-submissions');

        // Apply the combined filter if present
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Get the filtered users
        $usersWithPermission = $query->paginate(5);

        return new ProcessorUserCollection($usersWithPermission);
    }

    public function getManagers(Request $request): ProcessorUserCollection
    {
        // User IDs
        $allUserIds = collect($request->processor_users);

        // Role User IDs
        $userRoles = User::query()->whereHas('roles', function (Builder $query) use ($request) {
            $query->whereIn('id', $request->processor_roles);
        })->pluck('id');

        // Combine all IDs and remove duplicate
        $allUserIds = $allUserIds->merge($userRoles)->unique();

        // Start the query
        $query = User::query()->whereIn('id', $allUserIds);

        // Get the filtered users
        $users = $query->get();

        return new ProcessorUserCollection($users);
    }

    public function statuses($model): array
    {
        $processStatuses = $model->statuses()->get();

        $statuses = [];
        if ($processStatuses) {
            foreach ($processStatuses as $processStatus) {
                $statuses[] = [
                    'id' => $processStatus->id,
                    'name' => $processStatus->name ?? '-',
                    'status' => $processStatus->status,
                    'status_text' => $processStatus->statusName(),
                ];
            }
        }

        return $statuses;
    }

    public function processes($model): array
    {
        $processItems = $model->processes()->orderBy('order')->get();

        $processes = [];
        foreach ($processItems as $process) {
            // Default actions
            $actions = [
                ['id' => null, 'status_id' => '', 'linked_process_id' => '', 'comment_required' => false],
                ['id' => null, 'status_id' => '', 'linked_process_id' => '', 'comment_required' => false],
            ];

            if ($process->actions()->exists()) {
                $actions = [];
                foreach ($process->actions as $action) {
                    $linkedProcessId = $action->linked_process_id ?? '';

                    if (empty($action->linked_process_id)) {
                        if ($action->is_revert_submitter) {
                            $linkedProcessId = FormProcess::REVERT_SUBMITTER;
                        }

                        if ($action->is_end_process) {
                            $linkedProcessId = FormProcess::END_PROCESS;
                        }
                    }

                    $actions[] = [
                        'id' => $action->id,
                        'status_id' => $action->status_id,
                        'linked_process_id' => $linkedProcessId,
                        'comment_required' => $action->comment_required,
                    ];
                }
            }

            $processorUsers = [];
            if ($process->processorUsers()->exists()) {
                $processorUsers = $process->processorUsers->pluck('user_id');
            }

            $processorRoles = [];
            if ($process->processorRoles()->exists()) {
                $processorRoles = $process->processorRoles->pluck('role_id');
            }

            $processes[] = [
                'id' => $process->id,
                'name' => $process->name,
                'status' => $process->status,
                'status_text' => $process->statusName(),
                'order' => $process->order,
                'actions' => $actions,
                'processor_users' => $processorUsers,
                'processor_roles' => $processorRoles,
                'decision_type' => $process->decision_type ?? FormProcess::DECISION_TYPE_ANY,
                'majority_percentage' => $process->majority_percentage ?? null,
                'manager_id' => $process->manager_id ?? null,
            ];
        }

        return $processes;
    }
}
