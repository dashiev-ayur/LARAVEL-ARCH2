<?php

declare(strict_types=1);

use App\Enums\OrgStatus;
use App\Models\Org;

use function Pest\Laravel\getJson;

test('api возвращает список организаций с пагинацией и сортировкой по id desc по умолчанию', function () {
    $o1 = Org::factory()->create(['name' => 'First']);
    $o2 = Org::factory()->create(['name' => 'Second']);
    $o3 = Org::factory()->create(['name' => 'Third']);

    $response = getJson(route('orgs.index'));

    $response->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.per_page', 15);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$o3->id, $o2->id, $o1->id]);
});

test('api ограничивает размер страницы параметром per_page', function () {
    Org::factory()->count(25)->create();

    $response = getJson(route('orgs.index', ['per_page' => 10]));

    $response->assertOk()
        ->assertJsonPath('meta.total', 25)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonCount(10, 'data');
});

test('api сортирует по колонке и направлению', function () {
    Org::factory()->create(['name' => 'Zebra Org']);
    Org::factory()->create(['name' => 'Alpha Org']);
    Org::factory()->create(['name' => 'Mike Org']);

    $response = getJson(route('orgs.index', [
        'sort' => 'name',
        'direction' => 'asc',
    ]));

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toBe(['Alpha Org', 'Mike Org', 'Zebra Org']);
});

test('api ищет организации по подстроке в названии без учёта регистра', function () {
    Org::factory()->create(['name' => 'Acme Corporation']);
    Org::factory()->create(['name' => 'Other LLC']);

    $response = getJson(route('orgs.index', ['search' => 'acme']));

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Acme Corporation');
});

test('api фильтрует по статусу и городу', function () {
    Org::factory()->create([
        'name' => 'A',
        'status' => OrgStatus::Enabled,
        'city' => 'Moscow',
    ]);
    Org::factory()->create([
        'name' => 'B',
        'status' => OrgStatus::Enabled,
        'city' => 'Berlin',
    ]);
    Org::factory()->create([
        'name' => 'C',
        'status' => OrgStatus::New,
        'city' => 'Moscow',
    ]);

    $response = getJson(route('orgs.index', [
        'filter' => [
            'status' => 'enabled',
            'city' => 'Moscow',
        ],
    ]));

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'A');
});

test('api возвращает 422 при недопустимой колонке сортировки', function () {
    getJson(route('orgs.index', ['sort' => 'not_a_column']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});

test('api возвращает 422 при per_page вне диапазона', function () {
    getJson(route('orgs.index', ['per_page' => 1001]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

test('api возвращает 422 при недопустимом ключе filter', function () {
    getJson(route('orgs.index', ['filter' => ['unknown_key' => 'x']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['filter']);
});
