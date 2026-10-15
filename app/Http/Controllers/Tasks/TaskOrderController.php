<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\UpdateTaskStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\ReorderTasksRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskOrderController extends Controller
{
    /**
     * Save the order of a list or Kanban column. Tasks dropped into another column take its status.
     */
    public function update(ReorderTasksRequest $request, Team $current_team, UpdateTaskStatus $updateTaskStatus): RedirectResponse
    {
        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $status = $request->has('status') ? TaskStatus::from($request->string('status')->toString()) : null;

        DB::transaction(function () use ($ids, $status, $current_team, $updateTaskStatus, $request) {
            $tasks = $current_team->tasks()->whereKey($ids)->get()->keyBy('id');

            foreach ($ids as $position => $id) {
                $task = $tasks->get($id);

                if (! $task) {
                    continue;
                }

                if ($status && $task->status !== $status) {
                    $task = $updateTaskStatus->handle($task, $status, $request->user());
                }

                $task->update(['position' => $position]);
            }
        });

        return back();
    }
}
