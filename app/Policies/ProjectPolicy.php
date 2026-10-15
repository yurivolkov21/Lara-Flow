<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

/**
 * Every member of a team can manage its projects.
 */
class ProjectPolicy
{
    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can create projects on the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can move the project to the trash.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can restore the project from the trash.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    protected function isMember(User $user, Project $project): bool
    {
        return $user->teams()->whereKey($project->team_id)->exists();
    }
}
