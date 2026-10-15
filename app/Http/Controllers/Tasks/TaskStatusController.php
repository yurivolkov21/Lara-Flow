<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\UpdateTaskStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskStatusController extends Controller
{
    /**
     * Change the task's status, for example when it is ticked off.
     */
    public function update(Request $request, Team $current_team, Task $task, UpdateTaskStatus $updateTaskStatus): RedirectResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        $updateTaskStatus->handle($task, TaskStatus::from($validated['status']), $request->user());

        return back();
    }
}
