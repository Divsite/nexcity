<?php

namespace App\Http\Controllers\Submissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSubmissionProcesses\CreateFormSubmissionProcessRequest;
use App\Http\Requests\FormSubmissions\UpdateFormSubmissionRequest;
use App\Http\Resources\Submissions\SubmissionResource;
use App\Jobs\FormSubmissions\UpdateFormSubmissionJob;
use App\Models\FormProcesses\FormProcess;
use App\Models\FormSubmissions\FormSubmission;
use App\Utilities\FormProcesses\ProcessUserCollector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function show($id, $type = null)
    {
        if (!submission_show()->has($type)) {
            return redirect()->route('my-submissions.index');
        }

        $parentBreadcrumbs = submission_show()->get($type);

        $model = FormSubmission::with(['form', 'files', 'processors'])
            ->findOrFail($id);

        $canView = false;

        if ($model->processors()->where('user_id', auth()->user()->id)->exists()) {
            $canView = true;
        }

        if ($model->created_by == auth()->user()->id) {
            $canView = true;
        }

        if (!$canView) {
            return redirect()->route('my-submissions.index');
        }

        return view('submissions/show', [
            'model' => new SubmissionResource($model),
            'parentBreadcrumbs' => $parentBreadcrumbs,
        ]);
    }

    public function edit($id)
    {
        $model = FormSubmission::with(['form', 'currentStatus'])->findOrFail($id);

        $canEdit = $model->created_by == auth()->user()->id && $model->currentStatus->is_revert_submitter;

        if (!$canEdit) {
            return redirect()->route('my-submissions.index');
        }

        $updateRouteName = "submission.update";

        return view('form-submissions/create-edit', [
            'submission' => true,
            'model' => $model,
            'formData' => $model->prepareEditInput(),
            'updateRouteName' => $updateRouteName,
        ]);
    }

    public function update(UpdateFormSubmissionRequest $request): JsonResponse
    {
        $submission = $request->formSubmission;
        $currentStatus = $submission->currentStatus()->first();
        $canEdit = $submission->created_by == auth()->user()->id && ($currentStatus && $currentStatus->is_revert_submitter);

        if ($request->isLastPage && $canEdit) {
            UpdateFormSubmissionJob::dispatch($request->formSubmission, $request->validated(), auth()->user(),
                App::currentLocale(), true);
        }

        // Redirect
        return response()->json([
            'success' => true,
            'current_page_index' => $request->current_page_index,
            'is_last_page' => $request->isLastPage
        ]);
    }

    public function updateProcess(CreateFormSubmissionProcessRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $submission = FormSubmission::query()->find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.submission_not_found')
            ], 404);
        }

        if (!$submission->form->processes()->exists()) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.process_not_found')
            ], 404);
        }

        // First process
        if ($submission->currentStatus->status->is_default && empty($submission->currentStatus->process)) {
            // Get first process
            $process = $submission->form->processes()->where('order', 1)->first();

            if (!$process) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.next_process_not_found')
                ], 404);
            }

            // Get action details
            $action = $process->actions()->where('status_id', $validated['status_id'])->first();

            if (!$action) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.status_not_found')
                ], 404);
            }

            // If process decision type is ANY
            // Any decision maker make the process, automatically proceed to next process
            if ($process->decision_type == FormProcess::DECISION_TYPE_ANY) {
                // Update current status
                $submission->currentStatus()->update([
                    'status_id' => $validated['status_id'],
                    'process_id' => $process->id,
                    'comment' => $validated['comment'],
                    'is_revert_submitter' => $action->is_revert_submitter,
                    'is_end_process' => $action->is_end_process,
                ]);

                // Final decision for that process
                $submission->statuses()->create([
                    'status_id' => $validated['status_id'],
                    'process_id' => $process->id,
                    'comment' => $validated['comment'],
                ]);

                // Delete all current task on the submission if exists
                // After submit or update submission, the first process will become current tasks
                if ($submission->currentTasks()->exists()) {
                    $submission->currentTasks()->delete();
                }

                // Generate current tasks for linked process
                if (!empty($action->linkedProcess) && !$action->is_revert_submitter && !$action->is_end_process) {
                    $nextProcess = $action->linkedProcess;

                    foreach (ProcessUserCollector::get($nextProcess) as $item) {
                        $submission->currentTasks()->create([
                            'user_id' => $item,
                            'process_id' => $nextProcess->id,
                        ]);
                    }
                }

                // Generate completed tasks for this process
                foreach (ProcessUserCollector::get($process) as $item) {
                    $submission->completedTasks()->create([
                        'user_id' => $item,
                        'process_id' => $process->id,
                    ]);
                }

                // Always keep all processor user decision for this process
                $submission->processes()->create([
                    'process_id' => $process->id,
                    'status_id' => $validated['status_id'],
                    'comment' => $validated['comment'],
                ]);
            }

            // If process decision type is MAJORITY or ALL
            // MAJORITY - Update current status and statuses if passed the majority percentage
            // ALL - Update current status and statuses if all processor has made the tasks
            if (in_array($process->decision_type,
                [FormProcess::DECISION_TYPE_MAJORITY, FormProcess::DECISION_TYPE_ALL])) {
                // Update process for this processor
                $submission->processes()->create([
                    'process_id' => $process->id,
                    'status_id' => $validated['status_id'],
                    'comment' => $validated['comment'],
                ]);

                // Delete current task for this processor
                $submission->currentTasks()
                    ->where('user_id', auth()->user()->id)
                    ->where('process_id', $process->id)
                    ->delete();

                // Create completed task for this processor
                $submission->completedTasks()->create([
                    'user_id' => auth()->user()->id,
                    'process_id' => $process->id,
                ]);

                $updateFinalDecision = false;

                if ($process->decision_type == FormProcess::DECISION_TYPE_MAJORITY) {
                    // Check if percentage has passed based on submission processes
                    // Get all process after current status updated at
                    $currentProcesses = $submission->processes()
                        ->where('process_id', $process->id)
                        ->where('created_at', '>', $submission->currentStatus->updated_at)
                        ->count();

                    // Total processor for this process
                    $totalProcessor = ProcessUserCollector::get($process)->count();

                    // Get current percentage
                    $currentProcessPercent = ($currentProcesses / $totalProcessor) * 100;

                    // Check if current process percentage is already greater than or equal to majority percentage
                    // If current process percentage is greater than or equal to majority percentage, then update current status and final decision for this process
                    if ($currentProcessPercent >= $process->majority_percentage) {
                        $updateFinalDecision = true;
                    }
                }

                if ($process->decision_type == FormProcess::DECISION_TYPE_ALL) {
                    // If current tasks is empty, meaning all task has been processed by all processor
                    if ($submission->currentTasks->isEmpty()) {
                        $updateFinalDecision = true;
                    }
                }

                if ($updateFinalDecision) {
                    // Get selected process detail based on this process manager ID
                    $selectedProcess = $submission->processes()
                        ->where('process_id', $process->id)
                        ->where('created_by', $process->manager_id)
                        ->where('created_at', '>', $submission->currentStatus->updated_at)
                        ->first();

                    if ($selectedProcess && !empty($selectedProcess->status) && !empty($selectedProcess->process)) {
                        $selectedProcessAction = $selectedProcess->process->actions()
                            ->where('status_id', $selectedProcess->status->id)
                            ->first();

                        if ($selectedProcessAction) {
                            // Update current status
                            $submission->currentStatus()->update([
                                'status_id' => $selectedProcess->status->id,
                                'process_id' => $process->id,
                                'comment' => $selectedProcess->comment,
                                'is_revert_submitter' => $selectedProcessAction->is_revert_submitter,
                                'is_end_process' => $selectedProcessAction->is_end_process,
                            ]);

                            // Final decision for that process
                            $submission->statuses()->create([
                                'status_id' => $selectedProcess->status->id,
                                'process_id' => $process->id,
                                'comment' => $selectedProcess->comment,
                                'created_by' => $selectedProcess->created_by,
                                'updated_by' => $selectedProcess->updated_by,
                            ]);

                            // Delete all current task on the submission if exists
                            if ($submission->currentTasks()->exists()) {
                                $submission->currentTasks()->delete();
                            }

                            // Generate current tasks for linked process
                            if (!empty($selectedProcessAction->linkedProcess) && !$selectedProcessAction->is_revert_submitter && !$selectedProcessAction->is_end_process) {
                                $nextProcess = $selectedProcessAction->linkedProcess;

                                foreach (ProcessUserCollector::get($nextProcess) as $item) {
                                    $submission->currentTasks()->create([
                                        'user_id' => $item,
                                        'process_id' => $nextProcess->id,
                                    ]);
                                }
                            }

                            // Generate completed tasks for this process for remaining processors
                            foreach (ProcessUserCollector::get($process) as $item) {
                                $completedTask = $submission->completedTasks()
                                    ->where('user_id', $item)
                                    ->where('process_id', $process->id)
                                    ->exists();

                                if (!$completedTask) {
                                    $submission->completedTasks()->create([
                                        'user_id' => $item,
                                        'process_id' => $process->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            if (!$submission->currentStatus) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.next_process_not_found')
                ], 404);
            }

            $currentStatus = $submission->currentStatus;

            // If revert to submitter cannot proceed update process
            if ($currentStatus->is_revert_submitter) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.the_latest_status_is_on_the_sender')
                ], 403);
            }

            // If ended process cannot proceed update process
            if ($currentStatus->is_end_process) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.the_submission_process_has_ended')
                ], 403);
            }

            if (!$currentStatus->process) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.process_not_found')
                ], 404);
            }

            if (!$currentStatus->status) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.status_not_found')
                ], 404);
            }

            // Get linked process details for next process
            $processAction = $currentStatus->process->actions()
                ->where('status_id', $currentStatus->status->id)
                ->first();

            if (!$processAction) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.action_status_not_found')
                ], 404);
            }

            // If linked process not exists
            if (!$processAction->linkedProcess) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.linked_process_not_found')
                ], 404);
            }

            // Linked process based on current process
            $process = $processAction->linkedProcess;

            // Get linked process action details
            $action = $process->actions()->where('status_id', $validated['status_id'])->first();

            if (!$action) {
                return response()->json([
                    'success' => false,
                    'title' => __('messages.error'),
                    'text' => __('messages.status_not_found')
                ], 404);
            }

            // If linked process decision type is ANY
            // Any decision maker make the process, automatically proceed to next process
            if ($process->decision_type == FormProcess::DECISION_TYPE_ANY) {
                $submission->currentStatus()->update([
                    'status_id' => $validated['status_id'],
                    'process_id' => $process->id,
                    'comment' => $validated['comment'],
                    'is_revert_submitter' => $action->is_revert_submitter,
                    'is_end_process' => $action->is_end_process,
                ]);

                // Final decision for that process
                $submission->statuses()->create([
                    'status_id' => $validated['status_id'],
                    'process_id' => $process->id,
                    'comment' => $validated['comment'],
                ]);

                // Delete current tasks for this process
                $submission->currentTasks()->delete();

                // Generate current tasks for linked process (next process)
                if (!empty($action->linkedProcess) && !$action->is_revert_submitter && !$action->is_end_process) {
                    $nextProcess = $action->linkedProcess;

                    foreach (ProcessUserCollector::get($nextProcess) as $item) {
                        $submission->currentTasks()->create([
                            'user_id' => $item,
                            'process_id' => $nextProcess->id,
                        ]);
                    }
                }

                // Delete completed tasks (previous tasks)
                $submission->completedTasks()->delete();

                // Generate completed tasks for this linked process
                foreach (ProcessUserCollector::get($process) as $item) {
                    $submission->completedTasks()->create([
                        'user_id' => $item,
                        'process_id' => $process->id,
                    ]);
                }

                // Always keep all processor user decision for this process
                $submission->processes()->create([
                    'process_id' => $process->id,
                    'status_id' => $validated['status_id'],
                    'comment' => $validated['comment'],
                ]);
            }

            // If process decision type is MAJORITY or ALL
            // MAJORITY - Update current status and statuses if passed the majority percentage
            // ALL - Update current status and statuses if all processor has made the tasks
            if (in_array($process->decision_type,
                [FormProcess::DECISION_TYPE_MAJORITY, FormProcess::DECISION_TYPE_ALL])) {
                // Update processes for this processor
                $submission->processes()->create([
                    'process_id' => $process->id,
                    'status_id' => $validated['status_id'],
                    'comment' => $validated['comment'],
                ]);

                // Delete current task for this processor
                $submission->currentTasks()
                    ->where('user_id', auth()->user()->id)
                    ->where('process_id', $process->id)
                    ->delete();

                // Create completed task for this processor
                $submission->completedTasks()->create([
                    'user_id' => auth()->user()->id,
                    'process_id' => $process->id,
                ]);

                $updateFinalDecision = false;

                if ($process->decision_type == FormProcess::DECISION_TYPE_MAJORITY) {
                    // Check if percentage has passed based on submission processes
                    // Get all process after current status updated at
                    $currentProcesses = $submission->processes()
                        ->where('process_id', $process->id)
                        ->where('created_at', '>', $submission->currentStatus->updated_at)
                        ->count();

                    // Total processor for this process
                    $totalProcessor = ProcessUserCollector::get($process)->count();

                    // Get current percentage
                    $currentProcessPercent = ($currentProcesses / $totalProcessor) * 100;

                    // Check if current process percentage is already greater than or equal to majority percentage
                    // If current process percentage is greater than or equal to majority percentage, then update current status and final decision for this process
                    if ($currentProcessPercent >= $process->majority_percentage) {
                        $updateFinalDecision = true;
                    }
                }

                if ($process->decision_type == FormProcess::DECISION_TYPE_ALL) {
                    // If current tasks is empty, meaning all task has been processed by all processor
                    if ($submission->currentTasks->isEmpty()) {
                        $updateFinalDecision = true;
                    }
                }

                if ($updateFinalDecision) {
                    // Get selected process detail based on this process manager ID
                    $selectedProcess = $submission->processes()
                        ->where('process_id', $process->id)
                        ->where('created_by', $process->manager_id)
                        ->where('created_at', '>', $submission->currentStatus->updated_at)
                        ->first();

                    if ($selectedProcess && !empty($selectedProcess->status) && !empty($selectedProcess->process)) {
                        $selectedProcessAction = $selectedProcess->process->actions()
                            ->where('status_id', $selectedProcess->status->id)
                            ->first();

                        if ($selectedProcessAction) {
                            // Update current status
                            $submission->currentStatus()->update([
                                'status_id' => $selectedProcess->status->id,
                                'process_id' => $process->id,
                                'comment' => $selectedProcess->comment,
                                'is_revert_submitter' => $selectedProcessAction->is_revert_submitter,
                                'is_end_process' => $selectedProcessAction->is_end_process,
                            ]);

                            // Final decision for that process
                            $submission->statuses()->create([
                                'status_id' => $selectedProcess->status->id,
                                'process_id' => $process->id,
                                'comment' => $selectedProcess->comment,
                                'created_by' => $selectedProcess->created_by,
                                'updated_by' => $selectedProcess->updated_by,
                            ]);

                            // Delete all current task on the submission if exists
                            if ($submission->currentTasks()->exists()) {
                                $submission->currentTasks()->delete();
                            }

                            // Generate current tasks for linked process
                            if (!empty($selectedProcessAction->linkedProcess) && !$selectedProcessAction->is_revert_submitter && !$selectedProcessAction->is_end_process) {
                                $nextProcess = $selectedProcessAction->linkedProcess;

                                foreach (ProcessUserCollector::get($nextProcess) as $item) {
                                    $submission->currentTasks()->create([
                                        'user_id' => $item,
                                        'process_id' => $nextProcess->id,
                                    ]);
                                }
                            }

                            // Delete completed tasks (previous process tasks)
                            $submission->completedTasks()->where('process_id', $currentStatus->process->id)->delete();

                            // Generate completed tasks for this process for remaining processors
                            foreach (ProcessUserCollector::get($process) as $item) {
                                $completedTask = $submission->completedTasks()
                                    ->where('user_id', $item)
                                    ->where('process_id', $process->id)
                                    ->exists();

                                if (!$completedTask) {
                                    $submission->completedTasks()->create([
                                        'user_id' => $item,
                                        'process_id' => $process->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        activity(__('messages.form_submissions'))
            ->causedBy(auth()->user())
            ->performedOn($submission)
            ->log(__('messages.form_submission_for_form_has_been_processed_during_process_with_a_status_of', [
                'formName' => $submission->form->name,
                'processName' => $process->name,
                'statusName' => $action->status->name,
            ]));

        return response()->json([
            'status' => true,
            'title' => __('messages.success'),
            'text' => __('messages.the_status_for_the_process_has_been_saved_successfully',
                ['processName' => Str::lower($process->name)]),
        ]);
    }

    public function getData($id): SubmissionResource|JsonResponse
    {
        $model = FormSubmission::with(['form', 'files', 'processors'])
            ->find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('messages.submission_not_found')
            ], 404);
        }

        $canView = false;

        if ($model->processors()->where('user_id', auth()->user()->id)->exists()) {
            $canView = true;
        }

        if ($model->created_by == auth()->user()->id) {
            $canView = true;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'title' => __('messages.error'),
                'text' => __('Unauthorized')
            ], 401);
        }

        return new SubmissionResource($model);
    }
}
