<?php

namespace App\Concerns;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Http\Resources\OrgResource;
use App\Models\Membership;
use App\Models\Org;
use App\Models\Team;
use App\Support\TeamPermissions;
use App\Support\UserTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

trait HasTeams
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all of the teams the user owns.
     *
     * @return HasManyThrough<Team, Membership, $this>
     */
    public function ownedTeams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Team::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'team_id',
        )->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Get the user's current organization.
     *
     * @return BelongsTo<Org, $this>
     */
    public function currentOrg(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'current_org_id');
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Switch to the given team.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $nextOrg = ($this->currentOrg && $this->currentOrg->team_id === $team->id)
            ? $this->currentOrg
            : $this->fallbackOrg($team);

        $this->update([
            'current_team_id' => $team->id,
            'current_org_id' => $nextOrg?->id,
        ]);
        $this->setRelation('currentTeam', $team);
        $this->setRelation('currentOrg', $nextOrg);

        URL::defaults(['current_team' => $team->slug]);

        return true;
    }

    /**
     * Switch to the given organization.
     */
    public function switchOrg(Org $org): bool
    {
        if (! $this->belongsToOrg($org)) {
            return false;
        }

        $this->update(['current_org_id' => $org->id]);
        $this->setRelation('currentOrg', $org);

        return true;
    }

    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Determine if the given team is the user's current team.
     */
    public function isCurrentTeam(Team $team): bool
    {
        return $this->current_team_id === $team->id;
    }

    /**
     * Determine if the given organization is the user's current organization.
     */
    public function isCurrentOrg(Org $org): bool
    {
        return $this->current_org_id === $org->id;
    }

    /**
     * Determine if the user can access the given organization.
     */
    public function belongsToOrg(Org $org): bool
    {
        if ($this->current_team_id === null || $org->team_id !== $this->current_team_id) {
            return false;
        }

        return $this->teams()->where('teams.id', $org->team_id)->exists();
    }

    /**
     * Determine if the user is the owner of the given team.
     */
    public function ownsTeam(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::Owner;
    }

    /**
     * Get the user's role on the given team.
     */
    public function teamRole(Team $team): ?TeamRole
    {
        return $this->teamMemberships()
            ->where('team_id', $team->id)
            ->first()
            ?->role;
    }

    /**
     * Get the user's teams as a collection of UserTeam objects.
     *
     * @return Collection<int, UserTeam>
     */
    public function toUserTeams(bool $includeCurrent = false): Collection
    {
        return $this->teams()
            ->get()
            ->map(fn (Team $team) => ! $includeCurrent && $this->isCurrentTeam($team) ? null : $this->toUserTeam($team))
            ->filter()
            ->values();
    }

    /**
     * Get the user's team as a UserTeam object.
     */
    public function toUserTeam(Team $team, bool $includeOrgs = false): UserTeam
    {
        $role = $this->teamRole($team);

        if ($includeOrgs) {
            $team->loadMissing('orgs');
        }

        /** @var list<array<string, mixed>> */
        $orgs = [];

        if ($includeOrgs) {
            $orgs = $team->orgs->map(function (Org $org) use ($team, $role): array {
                $data = (new OrgResource($org))->toArray(request());
                $data['team_id'] = $org->team_id;
                $data['team'] = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'isPersonal' => $team->is_personal,
                    'role' => $role?->value,
                    'roleLabel' => $role?->label(),
                ];

                return $data;
            })->all();
        }

        return new UserTeam(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            isPersonal: $team->is_personal,
            role: $role?->value,
            roleLabel: $role?->label(),
            isCurrent: $this->isCurrentTeam($team),
            orgs: $orgs,
        );
    }

    /**
     * Get the standard permissions for a team as a TeamPermissions object.
     */
    public function toTeamPermissions(Team $team): TeamPermissions
    {
        $role = $this->teamRole($team);

        return new TeamPermissions(
            canUpdateTeam: $role?->hasPermission(TeamPermission::UpdateTeam) ?? false,
            canDeleteTeam: $role?->hasPermission(TeamPermission::DeleteTeam) ?? false,
            canAddMember: $role?->hasPermission(TeamPermission::AddMember) ?? false,
            canUpdateMember: $role?->hasPermission(TeamPermission::UpdateMember) ?? false,
            canRemoveMember: $role?->hasPermission(TeamPermission::RemoveMember) ?? false,
            canCreateInvitation: $role?->hasPermission(TeamPermission::CreateInvitation) ?? false,
            canCancelInvitation: $role?->hasPermission(TeamPermission::CancelInvitation) ?? false,
        );
    }

    public function fallbackTeam(?Team $excluding = null): ?Team
    {
        return $this->teams()
            ->when($excluding, fn ($query) => $query->where('teams.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(teams.name)')
            ->first();
    }

    /**
     * Get fallback organization for a specific team or current team.
     */
    public function fallbackOrg(?Team $team = null): ?Org
    {
        $teamId = $team?->id ?? $this->current_team_id;

        if ($teamId === null) {
            return null;
        }

        return Org::query()
            ->where('team_id', $teamId)
            ->orderByRaw('LOWER(name)')
            ->first();
    }

    /**
     * Determine if the user has the given permission on the team.
     */
    public function hasTeamPermission(Team $team, TeamPermission $permission): bool
    {
        return $this->teamRole($team)?->hasPermission($permission) ?? false;
    }
}
