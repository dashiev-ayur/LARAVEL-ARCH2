<?php

use App\Enums\PostType;
use App\PostTypes\Handlers\PagePostTypeHandler;
use App\PostTypes\PostTypeHandlerFactory;
use Tests\TestCase;

uses(TestCase::class);

test('фабрика отдаёт handler с подписями из PHP-класса типа', function () {
    $factory = app(PostTypeHandlerFactory::class);

    $page = $factory->make(PostType::Page);

    expect($page)->toBeInstanceOf(PagePostTypeHandler::class)
        ->and($page->getFilterButtonTitle())->toBe('Страницы')
        ->and($page->getNewButtonTitle())->toBe('Новая страница');

    $data = $page->toData();
    expect($data->toInertiaArray())->toBe([
        'filterButtonTitle' => 'Страницы',
        'newButtonTitle' => 'Новая страница',
    ]);
});
