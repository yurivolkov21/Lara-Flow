<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectSort;
use App\Enums\ProjectView;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_can_be_created()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this
            ->actingAs($user)
            ->post(route('projects.store', $team), [
                'name' => 'Home',
                'color' => 'green',
                'icon' => 'house',
                'default_view' => 'kanban',
            ]);

        $project = $team->projects()->firstOrFail();

        $response->assertRedirect(route('projects.show', [$team, $project]));
        $this->assertSame('Home', $project->name);
        $this->assertSame('house', $project->icon);
        $this->assertSame(ProjectView::Kanban, $project->default_view);
        $this->assertSame(ProjectSort::Due, $project->sort_by);
    }

    public function test_project_colors_must_come_from_the_palette()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('projects.store', $user->currentTeam), [
                'name' => 'Home',
                'color' => '#ff0000',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_the_project_page_shows_its_tasks_with_the_saved_view()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create(['default_view' => 'kanban']);
        Task::factory()->inProject($project)->create(['title' => 'Paint the fence']);
        Task::factory()->for($user->currentTeam)->create(['title' => 'Inbox task']);

        $this
            ->actingAs($user)
            ->get(route('projects.show', [$user->currentTeam, $project]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/index')
                ->where('view', 'project')
                ->where('display', 'kanban')
                ->where('project.id', $project->id)
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Paint the fence'));
    }

    public function test_the_display_can_be_switched_with_a_query_parameter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create(['default_view' => 'kanban']);

        $this
            ->actingAs($user)
            ->get(route('projects.show', [$user->currentTeam, $project, 'display' => 'list']))
            ->assertInertia(fn (Assert $page) => $page->where('display', 'list'));
    }

    public function test_projects_can_be_updated()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create();

        $this
            ->actingAs($user)
            ->patch(route('projects.update', [$user->currentTeam, $project]), [
                'name' => 'Renamed',
                'sort_by' => 'manual',
            ])
            ->assertRedirect();

        $project->refresh();
        $this->assertSame('Renamed', $project->name);
        $this->assertSame(ProjectSort::Manual, $project->sort_by);
    }

    public function test_projects_can_be_archived_and_unarchived()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create();

        $this->actingAs($user)->post(route('projects.archive', [$user->currentTeam, $project]));
        $this->assertNotNull($project->fresh()->archived_at);

        $this->actingAs($user)->delete(route('projects.unarchive', [$user->currentTeam, $project]));
        $this->assertNull($project->fresh()->archived_at);
    }

    public function test_every_team_member_can_manage_projects()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $project = Project::factory()->for($team)->create();

        $this
            ->actingAs($member)
            ->patch(route('projects.update', [$team, $project]), ['name' => 'By a member'])
            ->assertRedirect();

        $this->assertSame('By a member', $project->fresh()->name);
    }

    public function test_projects_of_another_team_are_not_found()
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->for($stranger->currentTeam)->create();

        $this
            ->actingAs($user)
            ->get(route('projects.show', [$user->currentTeam, $project]))
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->patch(route('projects.update', [$user->currentTeam, $project]), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->get(route('projects.show', [$stranger->currentTeam, $project]))
            ->assertForbidden();
    }

    public function test_deleting_a_project_moves_it_and_its_tasks_to_the_trash()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user->currentTeam)->create();
        $tasks = Task::factory()->count(2)->inProject($project)->create();

        $this
            ->actingAs($user)
            ->delete(route('projects.destroy', [$user->currentTeam, $project]))
            ->assertRedirect(route('tasks.inbox', $user->currentTeam));

        $this->assertSoftDeleted($project);
        $deletedAt = $project->fresh()?->deleted_at ?? Project::withTrashed()->findOrFail($project->id)->deleted_at;

        foreach ($tasks as $task) {
            $this->assertSoftDeleted($task);
            $this->assertEquals($deletedAt, Task::withTrashed()->findOrFail($task->id)->deleted_at);
        }
    }

    public function test_the_sidebar_lists_active_and_archived_projects_with_counts()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        Project::factory()->for($team)->create(['name' => 'Active']);
        Project::factory()->for($team)->archived()->create(['name' => 'Old']);
        Task::factory()->for($team)->create();

        $this
            ->actingAs($user)
            ->get(route('tasks.inbox', $team))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sidebar.projects.0.name', 'Active')
                ->has('sidebar.projects', 1)
                ->where('sidebar.archivedProjects.0.name', 'Old')
                ->where('sidebar.counts.inbox', 1));
    }
}
