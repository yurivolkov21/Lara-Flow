<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_created_in_the_inbox()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('tasks.store', $user->currentTeam), [
                'title' => 'Buy milk',
                'priority' => 'high',
                'due_on' => '2026-10-20',
                'due_time' => '09:30',
            ])
            ->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame($user->currentTeam->id, $task->team_id);
        $this->assertNull($task->project_id);
        $this->assertSame($user->id, $task->created_by);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertSame('2026-10-20', $task->due_on?->toDateString());
        $this->assertSame('09:30', $task->due_time);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'due_on' => '2026-10-20', 'due_time' => '09:30:00']);
    }

    public function test_tasks_can_be_created_in_a_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create();

        $this
            ->actingAs($user)
            ->post(route('tasks.store', $user->currentTeam), [
                'title' => 'Plan the trip',
                'project_id' => $project->id,
                'status' => 'doing',
            ]);

        $task = Task::firstOrFail();
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame(TaskStatus::Doing, $task->status);
    }

    public function test_tasks_cannot_be_put_in_another_teams_project()
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('tasks.store', $user->currentTeam), [
                'title' => 'Sneaky',
                'project_id' => $otherProject->id,
            ])
            ->assertSessionHasErrors('project_id');
    }

    public function test_tasks_cannot_be_put_in_an_archived_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->archived()->create();

        $this
            ->actingAs($user)
            ->post(route('tasks.store', $user->currentTeam), [
                'title' => 'Late',
                'project_id' => $project->id,
            ])
            ->assertSessionHasErrors('project_id');
    }

    public function test_tasks_can_be_updated()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->create();

        $this
            ->actingAs($user)
            ->patch(route('tasks.update', [$user->currentTeam, $task]), [
                'title' => 'Renamed',
                'description' => 'More detail',
                'due_on' => null,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('Renamed', $task->title);
        $this->assertSame('More detail', $task->description);
        $this->assertNull($task->due_on);
    }

    public function test_tasks_of_another_team_cannot_be_touched()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('tasks.update', [$user->currentTeam, $task]), ['title' => 'Hijacked'])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->patch(route('tasks.status', [$user->currentTeam, $task]), ['status' => 'done'])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->delete(route('tasks.destroy', [$user->currentTeam, $task]))
            ->assertNotFound();

        $this->assertNotSame('Hijacked', $task->fresh()->title);
        $this->assertNotSoftDeleted($task);
    }

    public function test_completing_a_task_sets_completed_at_and_reopening_clears_it()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->create();

        $this->actingAs($user)->patch(route('tasks.status', [$user->currentTeam, $task]), ['status' => 'done']);

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);

        $this->actingAs($user)->patch(route('tasks.status', [$user->currentTeam, $task]), ['status' => 'todo']);

        $task->refresh();
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_completing_a_recurring_task_spawns_the_next_one_with_a_fresh_checklist()
    {
        Carbon::setTestNow('2026-10-15 08:00:00');

        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create();
        $task = Task::factory()->inProject($project)->create([
            'title' => 'Pay the electricity bill',
            'priority' => 'high',
            'due_on' => '2026-10-05',
            'due_time' => '18:00',
            'recurrence' => ['frequency' => 'monthly', 'interval' => 1, 'month_day' => 5],
        ]);
        TaskItem::factory()->for($task)->done()->create(['title' => 'Check the meter', 'position' => 0]);
        TaskItem::factory()->for($task)->create(['title' => 'Pay online', 'position' => 1]);

        $this->actingAs($user)->patch(route('tasks.status', [$user->currentTeam, $task]), ['status' => 'done']);

        $task->refresh();
        $next = $task->nextTask;

        $this->assertNotNull($next);
        $this->assertSame('Pay the electricity bill', $next->title);
        $this->assertSame($project->id, $next->project_id);
        $this->assertSame(TaskPriority::High, $next->priority);
        $this->assertSame(TaskStatus::Todo, $next->status);
        $this->assertSame('2026-11-05', $next->due_on?->toDateString());
        $this->assertSame('18:00', $next->due_time);
        $this->assertEquals($task->recurrence, $next->recurrence);
        $this->assertSame(['Check the meter', 'Pay online'], $next->items->pluck('title')->all());
        $this->assertFalse($next->items->contains('is_done', true));
    }

    public function test_reopening_and_completing_again_does_not_spawn_a_duplicate()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->daily()->create(['due_on' => now()->toDateString()]);
        $route = route('tasks.status', [$user->currentTeam, $task]);

        $this->actingAs($user)->patch($route, ['status' => 'done']);
        $this->actingAs($user)->patch($route, ['status' => 'todo']);
        $this->actingAs($user)->patch($route, ['status' => 'done']);

        $this->assertSame(2, Task::count());
    }

    public function test_the_next_due_date_uses_the_users_timezone()
    {
        Carbon::setTestNow('2026-10-15 20:00:00');

        $user = User::factory()->create(['timezone' => 'Asia/Ho_Chi_Minh']);
        $task = Task::factory()->for($user->currentTeam)->daily()->create(['due_on' => '2026-10-15']);

        $this->actingAs($user)->patch(route('tasks.status', [$user->currentTeam, $task]), ['status' => 'done']);

        // 20:00 UTC is already 03:00 on the 16th in Ho Chi Minh City, so the next task is due on the 17th.
        $this->assertSame('2026-10-17', $task->fresh()->nextTask?->due_on?->toDateString());
    }

    public function test_recurring_tasks_need_a_due_date()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('tasks.store', $user->currentTeam), [
                'title' => 'Water the plants',
                'recurrence' => ['frequency' => 'weekly'],
            ])
            ->assertSessionHasErrors('due_on');
    }

    public function test_weekly_and_monthly_rules_default_to_the_due_date()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tasks.store', $user->currentTeam), [
            'title' => 'Weekly review',
            'due_on' => '2026-10-16',
            'recurrence' => ['frequency' => 'weekly'],
        ]);
        $this->actingAs($user)->post(route('tasks.store', $user->currentTeam), [
            'title' => 'Rent',
            'due_on' => '2026-10-28',
            'recurrence' => ['frequency' => 'monthly'],
        ]);

        $this->assertSame([5], Task::where('title', 'Weekly review')->firstOrFail()->recurrence?->weekdays);
        $this->assertSame(28, Task::where('title', 'Rent')->firstOrFail()->recurrence?->monthDay);
    }

    public function test_changing_the_status_through_update_also_spawns_the_next_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->daily()->create(['due_on' => now()->toDateString()]);

        $this
            ->actingAs($user)
            ->patch(route('tasks.update', [$user->currentTeam, $task]), ['status' => 'done'])
            ->assertRedirect();

        $this->assertNotNull($task->fresh()->next_task_id);
    }

    public function test_dropping_tasks_into_a_kanban_column_changes_status_and_order()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $first = Task::factory()->for($team)->create(['status' => 'doing', 'position' => 0]);
        $moved = Task::factory()->for($team)->create(['status' => 'todo', 'position' => 0]);
        $foreign = Task::factory()->create(['status' => 'todo']);

        $this
            ->actingAs($user)
            ->post(route('tasks.order', $team), [
                'status' => 'doing',
                'ids' => [$moved->id, $first->id, $foreign->id],
            ])
            ->assertRedirect();

        $this->assertSame(TaskStatus::Doing, $moved->fresh()->status);
        $this->assertSame(0, $moved->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(TaskStatus::Todo, $foreign->fresh()->status);
    }

    public function test_dropping_a_recurring_task_into_done_spawns_the_next_one()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->daily()->create(['due_on' => now()->toDateString()]);

        $this->actingAs($user)->post(route('tasks.order', $user->currentTeam), [
            'status' => 'done',
            'ids' => [$task->id],
        ]);

        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertNotNull($task->fresh()->next_task_id);
    }

    public function test_deleting_a_task_moves_it_to_the_trash()
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user->currentTeam)->create();

        $this
            ->actingAs($user)
            ->delete(route('tasks.destroy', [$user->currentTeam, $task]))
            ->assertRedirect();

        $this->assertSoftDeleted($task);
    }
}
