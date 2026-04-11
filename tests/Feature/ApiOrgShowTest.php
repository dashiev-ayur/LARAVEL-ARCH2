<?php

use App\Enums\OrgStatus;
use App\Models\Org;

use function Pest\Laravel\getJson;

test('api возвращает организацию по id', function () {
    $org = Org::factory()->create([
        'name' => 'Acme Co',
        'status' => OrgStatus::Enabled,
    ]);

    $response = getJson(route('orgs.show', $org));

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $org->id)
        ->assertJsonPath('data.name', 'Acme Co')
        ->assertJsonPath('data.slug', $org->slug)
        ->assertJsonPath('data.status', 'enabled');
});

test('api возвращает 404 для несуществующей организации', function () {
    getJson('/api/orgs/999999')->assertNotFound();
});
