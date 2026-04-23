<?php

use App\Models\Org;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('inertia shared current team includes orgs for that team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = Org::factory()->create(['team_id' => $team->id]);
    $user->update(['current_org_id' => $org->id]);
    $user->refresh();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('currentTeam.orgs', 1)
            ->where('currentTeam.orgs.0.name', $org->name)
            ->has('orgs', 1)
            ->where('orgs.0.id', $org->id)
            ->where('currentOrg.id', $org->id),
        );
});
