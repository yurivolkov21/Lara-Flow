<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\User;
use InvalidArgumentException;

class SpawnNextRecurringTask
{
    /**
     * Create the next instance of a recurring task, with its checklist reset.
     */
    public function handle(Task $task, User $actor): Task
    {
        $recurrence = $task->recurrence ?? throw new InvalidArgumentException('The task does not repeat.');

        $today = $actor->today();
        $from = $task->due_on !== null ? $today->setDateFrom($task->due_on) : $today;

        $next = $task->team->tasks()->create([
            'project_id' => $task->project_id,
            'created_by' => $task->created_by,
            'title' => $task->title,
            'description' => $task->description,
            'status' => TaskStatus::Todo,
            'priority' => $task->priority,
            'due_on' => $recurrence->nextDueDate($from, $today)->toDateString(),
            'due_time' => $task->due_time,
            'recurrence' => $recurrence,
            'position' => Task::nextPosition($task->team_id, $task->project_id, TaskStatus::Todo),
        ]);

        $next->items()->createMany(
            $task->items->map(fn (TaskItem $item) => [
                'title' => $item->title,
                'is_done' => false,
                'position' => $item->position,
            ])->all(),
        );

        return $next;
    }
}
