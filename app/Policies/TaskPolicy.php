<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

/**
 * Every member of a team can manage its tasks.
 */
class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        return $this->isMember($user, $task);
    }

    /**
     * Determine whether the user can create tasks on the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->isMember($user, $task);
    }

    /**
     * Determine whether the user can move the task to the trash.
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->isMember($user, $task);
    }

    /**
     * Determine whether the user can restore the task from the trash.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->isMember($user, $task);
    }

    /**
     * Determine whether the user can permanently delete the task.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $this->isMember($user, $task);
    }

    protected function isMember(User $user, Task $task): bool
    {
        return $user->teams()->whereKey($task->team_id)->exists();
    }
}
