<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\UpdateTaskStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\SaveTaskRequest;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function __construct(private UpdateTaskStatus $updateTaskStatus)
    {
        //
    }

    /**
     * Create a task.
     */
    public function store(SaveTaskRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [Task::class, $current_team]);

        $attributes = $request->taskAttributes();
        $status = TaskStatus::tryFrom((string) ($attributes['status'] ?? '')) ?? TaskStatus::Todo;
        $projectId = isset($attributes['project_id']) ? (int) $attributes['project_id'] : null;

        $current_team->tasks()->create([
            ...Arr::except($attributes, ['status']),
            'status' => $status,
            'completed_at' => $status === TaskStatus::Done ? now() : null,
            'created_by' => $request->user()->id,
            'position' => Task::nextPosition($current_team->id, $projectId, $status),
        ]);

        return back();
    }

    /**
     * Update the task's details. A status change goes through the same rules as ticking it off.
     */
    public function update(SaveTaskRequest $request, Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $attributes = $request->taskAttributes();

        $task->update(Arr::except($attributes, ['status']));

        if (isset($attributes['status']) && is_string($attributes['status'])) {
            $status = TaskStatus::from($attributes['status']);

            if ($status !== $task->status) {
                $this->updateTaskStatus->handle($task, $status, $request->user());
            }
        }

        return back();
    }

    /**
     * Move the task to the trash. For a recurring task this also stops the series.
     */
    public function destroy(Request $request, Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task moved to the trash.')]);

        return back();
    }
}
