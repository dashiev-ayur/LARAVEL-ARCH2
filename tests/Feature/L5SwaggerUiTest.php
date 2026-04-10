<?php

use function Pest\Laravel\get;

test('l5-swagger ui page includes valid swagger config in data attribute', function () {
    $response = get('/api/documentation');

    $response->assertSuccessful();

    $html = $response->getContent();
    expect($html)->toContain('id="swagger-ui"')
        ->and($html)->toContain('data-swagger-ui-config');

    $dom = new DOMDocument;
    @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $swaggerDiv = $dom->getElementById('swagger-ui');

    expect($swaggerDiv)->not->toBeNull();

    $rawJson = html_entity_decode(
        $swaggerDiv->getAttribute('data-swagger-ui-config'),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    expect($rawJson)->not->toBeEmpty();

    $config = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['urls', 'documentationTitle', 'csrfToken', 'oauth2RedirectUrl'])
        ->and($config['urls'])->toBeArray();
});
