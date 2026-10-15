<?php

namespace App\Actions\Trash;

use App\Enums\TrashRetention;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Carbon;

class PruneTrash
{
    /**
     * How long a deleted team is kept before it is removed for good, with all its data.
     */
    public const TEAM_RETENTION_DAYS = 30;

    /**
     * Permanently delete trashed projects and tasks past each team's retention period,
     * and teams that were deleted more than 30 days ago.
     *
     * Cutoffs are computed in PHP per retention level so the queries work the same on SQLite and MySQL.
     *
     * @return array{projects: int, tasks: int, teams: int}
     */
    public function handle(?Carbon $now = null): array
    {
        $now ??= now();
        $deleted = ['projects' => 0, 'tasks' => 0, 'teams' => 0];

        foreach (TrashRetention::cases() as $retention) {
            $cutoff = $now->copy()->subDays($retention->value);
            $teamIds = Team::withTrashed()->where('trash_retention_days', $retention->value)->select('id');

            $deleted['projects'] += (int) Project::onlyTrashed()
                ->whereIn('team_id', $teamIds)
                ->where('deleted_at', '<', $cutoff)
                ->forceDelete();

            $deleted['tasks'] += (int) Task::onlyTrashed()
                ->whereIn('team_id', $teamIds)
                ->where('deleted_at', '<', $cutoff)
                ->forceDelete();
        }

        $deleted['teams'] = (int) Team::onlyTrashed()
            ->where('deleted_at', '<', $now->copy()->subDays(self::TEAM_RETENTION_DAYS))
            ->forceDelete();

        return $deleted;
    }
}
