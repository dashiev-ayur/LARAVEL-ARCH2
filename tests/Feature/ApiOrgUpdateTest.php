<?php

declare(strict_types=1);

use App\Models\Org;

use function Pest\Laravel\patchJson;

test('api частично обновляет организацию: отсутствующие поля не меняются', function () {
    $org = Org::factory()->create([
        'name' => 'Old Name',
        'about' => 'About',
        'city' => 'Moscow',
    ]);

    $response = patchJson(route('orgs.update', $org), [
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $org->id)
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.about', 'About')
        ->assertJsonPath('data.city', 'Moscow');

    $org->refresh();
    expect($org->name)->toBe('New Name');
    expect($org->about)->toBe('About');
    expect($org->city)->toBe('Moscow');
});

test('api очищает nullable поля при передаче null', function () {
    $org = Org::factory()->create([
        'about' => 'About',
        'city' => 'Moscow',
    ]);

    $response = patchJson(route('orgs.update', $org), [
        'about' => null,
        'city' => null,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $org->id)
        ->assertJsonPath('data.about', null)
        ->assertJsonPath('data.city', null);

    $org->refresh();
    expect($org->about)->toBeNull();
    expect($org->city)->toBeNull();
});

test('api регенерирует slug при передаче slug = null', function () {
    $org = Org::factory()->create([
        'name' => 'Acme Inc',
        'slug' => 'custom-slug',
    ]);

    $response = patchJson(route('orgs.update', $org), [
        'slug' => null,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $org->id);

    $org->refresh();
    expect($org->slug)->not->toBe('custom-slug');
    expect($org->slug)->toBeString();
    expect($org->slug)->not->toBeEmpty();
});

test('api возвращает 422 при попытке установить неуникальный slug', function () {
    $existing = Org::factory()->create(['slug' => 'taken-slug']);
    $org = Org::factory()->create(['slug' => 'free-slug']);

    patchJson(route('orgs.update', $org), [
        'slug' => $existing->slug,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});
