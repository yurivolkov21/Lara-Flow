<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class TrashProject
{
    /**
     * Move the project and its tasks to the trash with the same deletion time,
     * so restoring the project brings back exactly those tasks.
     */
    public function handle(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $deletedAt = now();

            $project->tasks()->update(['deleted_at' => $deletedAt]);

            $project->forceFill(['deleted_at' => $deletedAt])->save();
        });
    }
}
