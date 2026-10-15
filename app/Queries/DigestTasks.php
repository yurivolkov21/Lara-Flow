<?php

namespace App\Queries;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DigestTasks
{
    /**
     * Get the user's unfinished tasks due on the given day and before it, across all of their teams.
     *
     * @return array{today: Collection<int, Task>, overdue: Collection<int, Task>}
     */
    public static function for(User $user, CarbonImmutable $today): array
    {
        $teamIds = $user->teams()->pluck('teams.id');
        $date = $today->toDateString();

        $open = fn () => Task::query()
            ->with(['project', 'team'])
            ->whereIn('team_id', $teamIds)
            ->where('status', '!=', TaskStatus::Done->value)
            ->where(fn (Builder $query) => $query
                ->whereNull('project_id')
                ->orWhereHas('project', fn (Builder $project) => $project->whereNull('archived_at')));

        return [
            'today' => $open()
                ->where('due_on', $date)
                ->orderByRaw('case when due_time is null then 1 else 0 end')
                ->orderBy('due_time')
                ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
                ->get(),
            'overdue' => $open()
                ->where('due_on', '<', $date)
                ->orderBy('due_on')
                ->get(),
        ];
    }
}
