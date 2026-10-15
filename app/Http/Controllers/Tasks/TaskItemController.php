<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\SaveTaskItemRequest;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskItemController extends Controller
{
    /**
     * Add a step to the task's checklist.
     */
    public function store(SaveTaskItemRequest $request, Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->items()->create([
            ...$request->validated(),
            'position' => (int) $task->items()->max('position') + 1,
        ]);

        return back();
    }

    /**
     * Rename a step or tick it off.
     */
    public function update(SaveTaskItemRequest $request, Team $current_team, Task $task, TaskItem $item): RedirectResponse
    {
        Gate::authorize('update', $task);

        $item->update($request->validated());

        return back();
    }

    /**
     * Remove a step from the checklist.
     */
    public function destroy(Team $current_team, Task $task, TaskItem $item): RedirectResponse
    {
        Gate::authorize('update', $task);

        $item->delete();

        return back();
    }

    /**
     * Save the order of the checklist after a drag and drop.
     */
    public function reorder(Request $request, Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        /** @var array{ids: array<int, int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($task, $validated) {
            foreach ($validated['ids'] as $position => $id) {
                $task->items()->whereKey($id)->update(['position' => $position]);
            }
        });

        return back();
    }
}
