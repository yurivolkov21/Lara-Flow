<?php

namespace App\Http\Middleware;

use App\Http\Resources\ProjectResource;
use App\Models\User;
use App\Queries\TaskViewQuery;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'locale' => $locale,
            'translations' => Inertia::once(fn () => Translations::for($locale))->as("translations.{$locale}"),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'sidebar' => fn () => $user ? $this->sidebar($request, $user) : null,
        ];
    }

    /**
     * Get the projects and task counts shown in the app sidebar.
     *
     * @return array{projects: array<int, mixed>, archivedProjects: array<int, mixed>, counts: array{today: int, overdue: int, inbox: int}}|null
     */
    protected function sidebar(Request $request, User $user): ?array
    {
        $team = $user->currentTeam;

        if (! $team) {
            return null;
        }

        $projects = $team->projects()->orderBy('position')->orderBy('id')->get();

        return [
            'projects' => ProjectResource::collection($projects->whereNull('archived_at')->values())->resolve($request),
            'archivedProjects' => ProjectResource::collection($projects->whereNotNull('archived_at')->values())->resolve($request),
            'counts' => (new TaskViewQuery($team, $user->today()))->counts(),
        ];
    }
}
