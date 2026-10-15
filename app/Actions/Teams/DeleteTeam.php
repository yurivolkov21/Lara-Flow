<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTeam
{
    /**
     * Soft-delete the team. Members who had it open are moved to their personal team.
     * The daily trash cleanup removes the team and its data for good after 30 days.
     */
    public function handle(Team $team, ?User $deletedBy = null): void
    {
        DB::transaction(function () use ($team, $deletedBy) {
            User::where('current_team_id', $team->id)
                ->when($deletedBy, fn ($query) => $query->where('id', '!=', $deletedBy->id))
                ->each(function (User $affectedUser) use ($team) {
                    $personalTeam = $affectedUser->personalTeam();

                    if ($personalTeam && $personalTeam->isNot($team)) {
                        $affectedUser->switchTeam($personalTeam);
                    }
                });

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });
    }
}
