<?php

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\TrashProject;
use App\Enums\ProjectView;
use App\Enums\TaskView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SaveProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Team;
use App\Queries\TaskViewQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Create a project.
     */
    public function store(SaveProjectRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [Project::class, $current_team]);

        $project = $current_team->projects()->create([
            ...$request->validated(),
            'position' => (int) $current_team->projects()->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', ['current_team' => $current_team, 'project' => $project]);
    }

    /**
     * Show the project's tasks.
     */
    public function show(Request $request, Team $current_team, Project $project): Response
    {
        Gate::authorize('view', $project);

        $today = $request->user()->today();
        $tasks = (new TaskViewQuery($current_team, $today))->get(TaskView::Project, $project, $project->sort_by);

        return Inertia::render('tasks/index', [
            'view' => TaskView::Project->value,
            'project' => ProjectResource::make($project)->resolve($request),
            'display' => ProjectView::tryFrom($request->string('display')->toString())->value ?? $project->default_view->value,
            'today' => $today->toDateString(),
            'tasks' => TaskResource::collection($tasks)->resolve($request),
        ]);
    }

    /**
     * Update the project's name, look, and display settings.
     */
    public function update(SaveProjectRequest $request, Team $current_team, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return back();
    }

    /**
     * Move the project and its tasks to the trash.
     */
    public function destroy(Team $current_team, Project $project, TrashProject $trashProject): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $trashProject->handle($project);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project moved to the trash.')]);

        return to_route('tasks.inbox', ['current_team' => $current_team]);
    }
}
