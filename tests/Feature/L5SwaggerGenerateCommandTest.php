<?php

use function Pest\Laravel\artisan;

test('l5-swagger generate command completes and writes api-docs.json', function () {
    artisan('l5-swagger:generate')->assertSuccessful();

    $jsonPath = storage_path('api-docs/api-docs.json');

    expect(file_exists($jsonPath))->toBeTrue();

    $contents = file_get_contents($jsonPath);
    expect($contents)->toContain('L5 Swagger UI')
        ->and($contents)->toContain('/api/health');
});
