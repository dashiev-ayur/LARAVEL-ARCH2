<?php

namespace App\Http\Middleware;

use App\Http\Resources\OrgResource;
use App\Models\Org;
use Illuminate\Http\Request;
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
        $currentTeam = $user?->currentTeam;
        $teamRole = $currentTeam ? $user?->teamRole($currentTeam) : null;
        $toSharedOrg = static function (Org $org) use ($request, $currentTeam, $teamRole): array {
            $org->loadMissing('team');

            $data = (new OrgResource($org))->toArray($request);
            $data['team_id'] = $org->team_id;
            $data['team'] = [
                'id' => $org->team->id,
                'name' => $org->team->name,
                'slug' => $org->team->slug,
                'isPersonal' => $org->team->is_personal,
                'role' => ($currentTeam && $currentTeam->id === $org->team_id) ? $teamRole?->value : null,
                'roleLabel' => ($currentTeam && $currentTeam->id === $org->team_id) ? $teamRole?->label() : null,
            ];

            return $data;
        };
        $teamOrgs = $currentTeam
            ? $currentTeam->orgs()->orderByRaw('LOWER(name)')->get()
            : collect();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam
                ? $user->toUserTeam($user->currentTeam, includeOrgs: true)
                : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'currentOrg' => fn () => $user?->currentOrg ? $toSharedOrg($user->currentOrg) : null,
            'orgs' => fn () => $teamOrgs->map(fn (Org $org): array => $toSharedOrg($org))->all(),
        ];
    }
}
