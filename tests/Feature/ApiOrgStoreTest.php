<?php

use App\Models\Org;

use function Pest\Laravel\postJson;

test('api создаёт организацию и возвращает 201', function () {
    $response = postJson(route('orgs.store'), [
        'name' => 'Acme Co',
    ]);

    $response->assertCreated();

    $id = $response->json('data.id');

    $response
        ->assertHeader('Location', '/api/orgs/'.$id)
        ->assertJsonPath('data.name', 'Acme Co')
        ->assertJsonPath('data.slug', 'acme-co')
        ->assertJsonPath('data.status', 'new')
        ->assertJsonPath('data.id', $id);

    expect(Org::query()->count())->toBe(1);
});

test('api возвращает 422 если name отсутствует', function () {
    postJson(route('orgs.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('api возвращает 422 при дублирующемся slug', function () {
    Org::factory()->create(['slug' => 'taken-slug']);

    postJson(route('orgs.store'), [
        'name' => 'Other',
        'slug' => 'taken-slug',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});
