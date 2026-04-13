<?php

declare(strict_types=1);

use App\Models\Org;

use function Pest\Laravel\deleteJson;

test('api удаляет организацию и возвращает 204', function () {
    $org = Org::factory()->create();

    deleteJson(route('orgs.destroy', $org))
        ->assertNoContent();

    expect(Org::query()->whereKey($org->id)->exists())->toBeFalse();
});

test('api возвращает 404 при удалении несуществующей организации', function () {
    deleteJson(route('orgs.destroy', ['org' => 99999999]))
        ->assertNotFound();
});
