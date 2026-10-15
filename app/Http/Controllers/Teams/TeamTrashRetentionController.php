<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TrashRetention;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamTrashRetentionController extends Controller
{
    /**
     * Change how long the team keeps deleted projects and tasks. Null means forever.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $validated = $request->validate([
            'trash_retention_days' => ['present', 'nullable', Rule::enum(TrashRetention::class)],
        ]);

        $team->update(['trash_retention_days' => $validated['trash_retention_days']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trash settings updated.')]);

        return back();
    }
}
