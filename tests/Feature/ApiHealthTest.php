<?php

use function Pest\Laravel\getJson;

test('GET /api/health возвращает JSON со статусом ok', function () {
    $response = getJson('/api/health');

    $response->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertJson(['status' => 'ok']);
});
