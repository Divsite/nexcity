<?php

namespace App\Http\Resources\Submissions;

use App\Models\FormSubmissions\FormSubmissionCurrentTask;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $submission = [
            'id' => $this->id,
            'form_id' => $this->form_id,
            'data' => $this->formData(),
            'file_exists' => $this->files()->exists(),
            'file_data' => $this->fileData(),
            'additional_info' => [
                'created_by' => $this->author->name ?? '-',
                'created_at' => $this->created_at->format('d/m/Y h:i A'),
                'updated_by' => $this->lastEditor->name ?? '-',
                'updated_at' => $this->updated_at->diffForHumans(),
            ],
            'latest_status' => $this->latestStatus(),
            'is_owner' => auth()->user()->id == $this->created_by,
            'edit_url' => route('submission.edit', $this->id),
        ];

        return [
            'submission' => $submission,
            'form' => $this->form(),
            'can_process_submissions' => auth()->user()->can('process-submissions'),
            'is_workflow_exists' => $this->form->processes()->exists(),
            'current_process' => $this->currentProcess(),
            'statuses' => $this->submissionStatuses(),
            'processes' => $this->submissionProcesses(),
        ];
    }

    public function form(): array
    {
        return [
            'id' => $this->form->id,
            'name' => $this->form->name,
        ];
    }

    public function fileData(): array
    {
        $files = [];

        if (!empty($this->files()->exists())) {
            foreach ($this->files as $file) {
                $files[] = [
                    'id' => $file->id,
                    'label' => $file->label,
                    'mime_type' => $file->mime_type,
                    'size' => format_bytes($file->size),
                    'url' => route('form-submission-files.show', $file->id),
                ];
            }
        }

        return $files;
    }

    public function currentProcess(): array
    {
        if (!$this->currentStatus) {
            return [
                'proceed_next_process' => false,
                'is_revert_submitter' => false,
                'is_end_process' => false,
                'is_processor' => false,
            ];
        }

        // Make sure is not revert submitter or ended process
        $proceedNextProcess = !$this->currentStatus->is_revert_submitter && !$this->currentStatus->is_end_process;

        if ($this->currentStatus->status->is_default && empty($this->currentStatus->process)) {
            $process = $this->form->processes()->where('order', 1)->first();
        } else {
            // Next process
            if ($proceedNextProcess && $this->currentStatus->process && $this->currentStatus->status) {
                $action = $this->currentStatus->process->actions()
                    ->where('status_id', $this->currentStatus->status->id)
                    ->first();

                // Proceed next process if linked process exists
                if ($action && $action->linkedProcess) {
                    $process = $action->linkedProcess;
                }
            }
        }

        if ($proceedNextProcess && !empty($process)) {
            $isUserProcessor = $process->processorUsers()
                ->where('user_id', auth()->user()->id)
                ->exists();

            $isUserRoleProcessor = $process->processorRoles()
                ->whereIn('role_id', auth()->user()->roles->pluck('id'))
                ->exists();

            // Check has current task for the process
            $hasCurrentTaskProcess = FormSubmissionCurrentTask::query()
                ->where('user_id', auth()->user()->id)
                ->where('process_id', $process->id)
                ->exists();

            $isProcessor = ($isUserProcessor || $isUserRoleProcessor) && $hasCurrentTaskProcess;

            $actions = [];
            if (!empty($process->actions)) {
                foreach ($process->actions as $action) {
                    $actions[] = [
                        'id' => $action->status->id,
                        'name' => $action->status->name,
                        'comment_required' => $action->comment_required,
                    ];
                }
            }

            return [
                'label' => __('messages.process', ['number' => $process->order]),
                'name' => $process->name,
                'actions' => $actions,
                'proceed_next_process' => true,
                'is_revert_submitter' => $this->currentStatus->is_revert_submitter,
                'is_end_process' => $this->currentStatus->is_end_process,
                'is_processor' => $isProcessor,
            ];
        }

        return [
            'proceed_next_process' => $proceedNextProcess,
            'is_revert_submitter' => $this->currentStatus->is_revert_submitter,
            'is_end_process' => $this->currentStatus->is_end_process,
            'is_processor' => false,
        ];
    }

    public function submissionStatuses(): array
    {
        $statuses = [];

        if ($this->statuses()->exists()) {
            $submissionStatuses = $this->statuses()->orderBy('created_at', 'desc')->get();

            $path = "/".User::AVATAR_PATH;

            foreach ($submissionStatuses as $submissionStatus) {
                $processName = null;

                if ($submissionStatus->status->is_default && empty($submissionStatus->process)) {
                    $statusName = $submissionStatus->status->name ?? __('messages.new_default_status');
                } else {
                    $statusName = $submissionStatus->status->name;
                    $processName = $submissionStatus->process->name;
                }

                $statuses[] = [
                    'id' => $submissionStatus->id,
                    'status_name' => $statusName,
                    'process_name' => $processName,
                    'comment' => $submissionStatus->comment ?? null,
                    'created_by' => $submissionStatus->createdBy->name ?? null,
                    'created_at' => $submissionStatus->created_at->format('d/m/Y h:i A'),
                    'processor_avatar' => !empty($submissionStatus->createdBy->avatar) ? $path.$submissionStatus->createdBy->avatar : null,
                ];
            }
        }

        return $statuses;
    }

    public function latestStatus(): ?array
    {
        if (!$this->currentStatus) {
            return null;
        }

        return [
            'status_id' => $this->currentStatus->status->id,
            'status_name' => $this->currentStatus->status->name ?? __('messages.new_default_status'),
            'process_id' => $this->currentStatus->process ? $this->currentStatus->process->id : null,
            'process_name' => $this->currentStatus->process ? $this->currentStatus->process->name : '-',
            'updated_at' => $this->currentStatus->updated_at->format('d/m/Y h:i A'),
        ];
    }

    public function submissionProcesses(): array
    {
        $processes = [];

        if ($this->processes()->exists()) {
            $submissionProcesses = $this->processes()->orderBy('created_at', 'desc')->get();

            $path = "/".User::AVATAR_PATH;

            foreach ($submissionProcesses as $submissionProcess) {
                $processName = '-';

                if ($submissionProcess->status->is_default && empty($submissionProcess->process)) {
                    $statusName = $submissionProcess->status->name ?? __('messages.new_default_status');
                } else {
                    $statusName = $submissionProcess->status->name;
                    $processName = $submissionProcess->process->name;
                }

                $processes[] = [
                    'id' => $submissionProcess->id,
                    'status_name' => $statusName,
                    'process_name' => $processName,
                    'comment' => $submissionProcess->comment ?? '-',
                    'created_by' => $submissionProcess->createdBy->name ?? '-',
                    'created_at' => $submissionProcess->created_at->format('d/m/Y h:i A'),
                    'processor_avatar' => !empty($submissionProcess->createdBy->avatar) ? $path.$submissionProcess->createdBy->avatar : null,
                ];
            }
        }

        return $processes;
    }
}
