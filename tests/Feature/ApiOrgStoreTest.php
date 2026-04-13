<?php

declare(strict_types=1);

use App\Models\Org;
use Illuminate\Support\Carbon;

use function Pest\Laravel\postJson;

test('api генерирует уникальный slug из name при коллизии', function () {
    Org::factory()->create([
        'name' => 'Acme Inc',
        'slug' => 'acme-inc',
    ]);

    $response = postJson(route('orgs.store'), [
        'name' => 'Acme Inc',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Acme Inc')
        ->assertJsonPath('data.slug', 'acme-inc-1');
});

test('api добавляет timestamp postfix если числовые postfix заняты', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-13 12:00:00'));

    Org::factory()->create(['slug' => 'acme-inc']);
    Org::factory()->create(['slug' => 'acme-inc-1']);
    Org::factory()->create(['slug' => 'acme-inc-2']);
    Org::factory()->create(['slug' => 'acme-inc-3']);

    $response = postJson(route('orgs.store'), [
        'name' => 'Acme Inc',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'acme-inc-'.Carbon::now()->timestamp);
});

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
