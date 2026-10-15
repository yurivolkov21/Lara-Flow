<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectArchiveController extends Controller
{
    /**
     * Archive the project. Its tasks leave the Today, Upcoming and Overdue screens.
     */
    public function store(Team $current_team, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project archived.')]);

        return back();
    }

    /**
     * Bring the project back from the archive.
     */
    public function destroy(Team $current_team, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update(['archived_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project restored from the archive.')]);

        return back();
    }
}
