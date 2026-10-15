<?php

namespace App\Http\Controllers\Tasks;

use App\Enums\ProjectView;
use App\Enums\TaskView;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Team;
use App\Queries\TaskViewQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskViewController extends Controller
{
    /**
     * Show tasks due today.
     */
    public function today(Request $request, Team $current_team): Response
    {
        return $this->render($request, $current_team, TaskView::Today);
    }

    /**
     * Show tasks due in the next seven days.
     */
    public function upcoming(Request $request, Team $current_team): Response
    {
        return $this->render($request, $current_team, TaskView::Upcoming);
    }

    /**
     * Show unfinished tasks whose due date has passed.
     */
    public function overdue(Request $request, Team $current_team): Response
    {
        return $this->render($request, $current_team, TaskView::Overdue);
    }

    /**
     * Show tasks that are not in any project.
     */
    public function inbox(Request $request, Team $current_team): Response
    {
        return $this->render($request, $current_team, TaskView::Inbox);
    }

    protected function render(Request $request, Team $team, TaskView $view): Response
    {
        $today = $request->user()->today();
        $tasks = (new TaskViewQuery($team, $today))->get($view);

        return Inertia::render('tasks/index', [
            'view' => $view->value,
            'project' => null,
            'display' => ProjectView::tryFrom($request->string('display')->toString())->value ?? ProjectView::List->value,
            'today' => $today->toDateString(),
            'tasks' => TaskResource::collection($tasks)->resolve($request),
        ]);
    }
}
