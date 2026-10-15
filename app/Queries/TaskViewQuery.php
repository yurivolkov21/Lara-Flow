<?php

namespace App\Queries;

use App\Enums\ProjectSort;
use App\Enums\TaskStatus;
use App\Enums\TaskView;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TaskViewQuery
{
    /**
     * How long finished tasks stay visible in the Inbox and project screens.
     */
    public const RECENTLY_DONE_DAYS = 7;

    /**
     * How many days ahead the Upcoming screen looks.
     */
    public const UPCOMING_DAYS = 7;

    public function __construct(
        protected Team $team,
        protected CarbonImmutable $today,
    ) {
        //
    }

    /**
     * Get the tasks shown on the given screen, sorted for display.
     *
     * @return Collection<int, Task>
     */
    public function get(TaskView $view, ?Project $project = null, ProjectSort $sort = ProjectSort::Due): Collection
    {
        $query = $this->team->tasks()
            ->with(['project', 'items'])
            ->getQuery();

        match ($view) {
            TaskView::Today => $this->inActiveProjects($query)
                ->where('due_on', $this->today->toDateString()),
            TaskView::Upcoming => $this->inActiveProjects($query)
                ->where('due_on', '>', $this->today->toDateString())
                ->where('due_on', '<=', $this->today->addDays(self::UPCOMING_DAYS)->toDateString()),
            TaskView::Overdue => $this->inActiveProjects($query)
                ->where('due_on', '<', $this->today->toDateString())
                ->where('status', '!=', TaskStatus::Done->value),
            TaskView::Inbox => $this->hideOldDoneTasks($query->whereNull('project_id')),
            TaskView::Project => $this->hideOldDoneTasks($query->where('project_id', $project?->id)),
        };

        return $this->sort($query, $sort)->get();
    }

    /**
     * Count the unfinished tasks on each built-in screen, for the sidebar.
     *
     * @return array{today: int, overdue: int, inbox: int}
     */
    public function counts(): array
    {
        $open = fn () => $this->inActiveProjects(
            $this->team->tasks()->getQuery()->where('status', '!=', TaskStatus::Done->value),
        );

        return [
            'today' => $open()->where('due_on', $this->today->toDateString())->count(),
            'overdue' => $open()->where('due_on', '<', $this->today->toDateString())->count(),
            'inbox' => $open()->whereNull('project_id')->count(),
        ];
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    protected function inActiveProjects(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull('project_id')
            ->orWhereHas('project', fn (Builder $project) => $project->whereNull('archived_at')));
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    protected function hideOldDoneTasks(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('status', '!=', TaskStatus::Done->value)
            ->orWhere('completed_at', '>=', now()->subDays(self::RECENTLY_DONE_DAYS)));
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    protected function sort(Builder $query, ProjectSort $sort): Builder
    {
        $query->orderByRaw('case when status = ? then 1 else 0 end', [TaskStatus::Done->value]);

        $byDue = fn () => $query
            ->orderByRaw('case when due_on is null then 1 else 0 end')
            ->orderBy('due_on')
            ->orderByRaw('case when due_time is null then 1 else 0 end')
            ->orderBy('due_time');

        $byPriority = fn () => $query->orderByRaw(
            "case priority when 'high' then 0 when 'medium' then 1 else 2 end",
        );

        match ($sort) {
            ProjectSort::Due => [$byDue(), $byPriority()],
            ProjectSort::Priority => [$byPriority(), $byDue()],
            ProjectSort::Manual => $query->orderBy('position'),
        };

        return $query->orderBy('id');
    }
}
