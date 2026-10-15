<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatus
{
    public function __construct(private SpawnNextRecurringTask $spawnNextTask)
    {
        //
    }

    /**
     * Change the task's status. Completing a recurring task spawns its next instance once.
     */
    public function handle(Task $task, TaskStatus $status, User $actor): Task
    {
        return DB::transaction(function () use ($task, $status, $actor) {
            $task = Task::whereKey($task->id)->lockForUpdate()->firstOrFail();

            $task->status = $status;
            $task->completed_at = $status === TaskStatus::Done
                ? ($task->completed_at ?? now())
                : null;

            if ($status === TaskStatus::Done && $task->recurrence !== null && $task->next_task_id === null) {
                $task->next_task_id = $this->spawnNextTask->handle($task, $actor)->id;
            }

            $task->save();

            return $task;
        });
    }
}
