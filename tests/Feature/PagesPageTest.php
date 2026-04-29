<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

test('guests are redirected to login from pages page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);

    $this->get(route('pages.index', [
        'current_team' => $team->slug,
        'current_org' => $org->slug,
    ]))->assertRedirect(route('login'));
});

test('authenticated users can open pages page', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $org = $team->orgs()->create([
        'name' => 'Test Org',
        'slug' => 'test-org',
        'status' => 'enabled',
    ]);
    $user->update(['current_org_id' => $org->id]);

    $this->actingAs($user)
        ->get(route('pages.index', [
            'current_team' => $team->slug,
            'current_org' => $org->slug,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pages/index'),
        );
});
