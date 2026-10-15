<?php

namespace App\Http\Controllers;

use App\Actions\Projects\RestoreProject;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    /**
     * Show the team's deleted projects and tasks.
     */
    public function index(Request $request, Team $current_team): Response
    {
        $projects = $current_team->projects()
            ->onlyTrashed()
            ->withCount(['tasks' => fn ($query) => $query->onlyTrashed()])
            ->latest('deleted_at')
            ->get();

        $tasks = $current_team->tasks()
            ->onlyTrashed()
            ->with('project')
            ->where(fn (Builder $query) => $query
                ->whereNull('project_id')
                ->orWhereHas('project'))
            ->latest('deleted_at')
            ->get();

        return Inertia::render('trash/index', [
            'projects' => $projects->map(fn (Project $project) => [
                ...ProjectResource::make($project)->resolve($request),
                'tasks_count' => $project->tasks_count,
            ]),
            'tasks' => TaskResource::collection($tasks)->resolve($request),
            'retentionDays' => $current_team->trash_retention_days,
        ]);
    }

    /**
     * Restore a project and the tasks that were deleted with it.
     */
    public function restoreProject(Team $current_team, Project $project, RestoreProject $restoreProject): RedirectResponse
    {
        Gate::authorize('restore', $project);

        $restoreProject->handle($project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project restored.')]);

        return back();
    }

    /**
     * Permanently delete a project and its tasks.
     */
    public function destroyProject(Team $current_team, Project $project): RedirectResponse
    {
        Gate::authorize('forceDelete', $project);

        $project->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted permanently.')]);

        return back();
    }

    /**
     * Restore a task.
     */
    public function restoreTask(Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('restore', $task);

        $task->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task restored.')]);

        return back();
    }

    /**
     * Permanently delete a task.
     */
    public function destroyTask(Team $current_team, Task $task): RedirectResponse
    {
        Gate::authorize('forceDelete', $task);

        $task->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted permanently.')]);

        return back();
    }

    /**
     * Permanently delete everything in the team's trash.
     */
    public function empty(Team $current_team): RedirectResponse
    {
        DB::transaction(function () use ($current_team) {
            $current_team->projects()->onlyTrashed()->forceDelete();
            $current_team->tasks()->onlyTrashed()->forceDelete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trash emptied.')]);

        return back();
    }
}
