<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class RestoreProject
{
    /**
     * Restore the project and the tasks that were trashed together with it.
     * Tasks deleted on their own before that stay in the trash.
     */
    public function handle(Project $project): void
    {
        DB::transaction(function () use ($project) {
            if ($project->deleted_at !== null) {
                Task::onlyTrashed()
                    ->where('project_id', $project->id)
                    ->where('deleted_at', $project->deleted_at)
                    ->update(['deleted_at' => null]);
            }

            $project->restore();
        });
    }
}
