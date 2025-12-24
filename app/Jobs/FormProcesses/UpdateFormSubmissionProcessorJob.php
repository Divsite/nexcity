<?php

namespace App\Jobs\FormProcesses;

use App\Models\FormProcesses\FormProcess;
use App\Utilities\FormProcesses\ProcessUserCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFormSubmissionProcessorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FormProcess $formProcess;

    public function __construct(FormProcess $formProcess)
    {
        $this->formProcess = $formProcess;
    }

    public function handle(): void
    {
        $processes = $this->formProcess->form->processes()->where('status', true)->get();

        if ($processes->isNotEmpty()) {
            $allUserIds = $processes->flatMap(function ($process) {
                return ProcessUserCollector::get($process);
            })->unique();

            $submissions = $this->formProcess->form->submissions;

            foreach ($submissions as $submission) {
                $existingUserIds = $submission->processors()->pluck('user_id');

                $newUserIds = $allUserIds->diff($existingUserIds);
                $submission->processors()->createMany($newUserIds->map(fn($userId) => ['user_id' => $userId]));

                $submission->processors()->whereNotIn('user_id', $allUserIds)->delete();

                $this->generateCurrentTasks($submission);
            }
        }
    }

    public function generateCurrentTasks($submission): void
    {
        // Generate current task only
        // Completed task cannot be touched because processor already made the process
        if (!empty($submission->currentStatus)) {
            if ($submission->currentStatus->status->is_default && empty($submission->currentStatus->process)) {
                // Get first process
                $process = $submission->form->processes()->where('order', 1)->first();

                if ($process) {
                    // Get processors based on this process
                    $processors = ProcessUserCollector::get($process);

                    // If process decision type is ANY
                    // For ANY, generate current tasks only because it will take 1 processor decision
                    if ($process->decision_type == FormProcess::DECISION_TYPE_ANY) {
                        // Each processor
                        foreach ($processors as $processor) {
                            $existingTask = $submission->currentTasks()
                                ->where('user_id', $processor)
                                ->where('process_id', $process->id)
                                ->exists();

                            // If not exists
                            if (!$existingTask) {
                                $submission->currentTasks()->create([
                                    'user_id' => $processor,
                                    'process_id' => $process->id,
                                ]);
                            }
                        }
                    }

                    if (in_array($process->decision_type,
                        [FormProcess::DECISION_TYPE_MAJORITY, FormProcess::DECISION_TYPE_ALL])) {
                        // Each processor
                        foreach ($processors as $processor) {
                            // Find has completed task
                            $existingCompletedTask = $submission->completedTasks()
                                ->where('user_id', $processor)
                                ->where('process_id', $process->id)
                                ->exists();

                            // If not exists completed tasks, create current tasks
                            if (!$existingCompletedTask) {
                                // Find has current task
                                $existingTask = $submission->currentTasks()
                                    ->where('user_id', $processor)
                                    ->where('process_id', $process->id)
                                    ->exists();

                                // If current task not exists
                                if (!$existingTask) {
                                    $submission->currentTasks()->create([
                                        'user_id' => $processor,
                                        'process_id' => $process->id,
                                    ]);
                                }
                            }
                        }
                    }

                    // Delete user that not on this process
                    $submission->currentTasks()
                        ->where('process_id', $process->id)
                        ->whereNotIn('user_id', $processors)
                        ->delete();
                }
            } else {
                $currentStatus = $submission->currentStatus;

                if (!empty($currentStatus->process) &&
                    !empty($currentStatus->status) &&
                    !$currentStatus->is_revert_submitter &&
                    !$currentStatus->is_end_process
                ) {
                    // Get linked process details for current tasks
                    $processAction = $currentStatus->process->actions()
                        ->where('status_id', $currentStatus->status->id)
                        ->first();

                    if ($processAction && $processAction->linkedProcess) {
                        // Get next process
                        $process = $processAction->linkedProcess;

                        $processors = ProcessUserCollector::get($process);

                        // If process decision type is ANY
                        // For ANY, generate current tasks only because it will take 1 processor decision
                        if ($process->decision_type == FormProcess::DECISION_TYPE_ANY) {
                            // Each processor
                            foreach ($processors as $processor) {
                                $existingTask = $submission->currentTasks()
                                    ->where('user_id', $processor)
                                    ->where('process_id', $process->id)
                                    ->exists();

                                // If not exists
                                if (!$existingTask) {
                                    $submission->currentTasks()->create([
                                        'user_id' => $processor,
                                        'process_id' => $process->id,
                                    ]);
                                }
                            }
                        }

                        if (in_array($process->decision_type,
                            [FormProcess::DECISION_TYPE_MAJORITY, FormProcess::DECISION_TYPE_ALL])) {
                            // Each processor
                            foreach ($processors as $processor) {
                                // Find has completed task
                                $existingCompletedTask = $submission->completedTasks()
                                    ->where('user_id', $processor)
                                    ->where('process_id', $process->id)
                                    ->exists();

                                // If not exists completed tasks, create current tasks
                                if (!$existingCompletedTask) {
                                    // Find has current task
                                    $existingTask = $submission->currentTasks()
                                        ->where('user_id', $processor)
                                        ->where('process_id', $process->id)
                                        ->exists();

                                    // If current task not exists
                                    if (!$existingTask) {
                                        $submission->currentTasks()->create([
                                            'user_id' => $processor,
                                            'process_id' => $process->id,
                                        ]);
                                    }
                                }
                            }
                        }

                        // Delete user that not on this process
                        $submission->currentTasks()
                            ->where('process_id', $process->id)
                            ->whereNotIn('user_id', $processors)
                            ->delete();
                    }
                }
            }
        }
    }
}
