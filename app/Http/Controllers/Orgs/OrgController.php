<?php

namespace App\Http\Controllers\Orgs;

use App\Application\Orgs\UseCases\CreateOrgUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orgs\StoreOrgRequest;
use App\Models\Org;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrgController extends Controller
{
    /**
     * Создать организацию в текущей команде пользователя.
     */
    public function store(StoreOrgRequest $request, CreateOrgUseCase $createOrg): RedirectResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_unless($user && $team && $user->belongsToTeam($team), 403);

        DB::transaction(function () use ($createOrg, $request, $team, $user): Org {
            $org = $createOrg->execute([
                ...$request->validated(),
                'team_id' => $team->id,
            ]);

            $user->switchOrg($org);

            return $org;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization created.')]);

        return to_route('dashboard');
    }
}
