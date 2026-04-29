<?php

use App\Enums\PostType;
use App\PostTypes\Handlers\NewsPostTypeHandler;
use App\PostTypes\PostTypeHandlerFactory;
use Tests\TestCase;

uses(TestCase::class);

test('фабрика отдаёт handler с подписями из PHP-класса типа', function () {
    $factory = app(PostTypeHandlerFactory::class);

    $news = $factory->make(PostType::News);

    expect($news)->toBeInstanceOf(NewsPostTypeHandler::class)
        ->and($news->getFilterButtonTitle())->toBe('Новости')
        ->and($news->getNewButtonTitle())->toBe('Новая новость');

    $data = $news->toData();
    expect($data->toInertiaArray())->toBe([
        'filterButtonTitle' => 'Новости',
        'newButtonTitle' => 'Новая новость',
    ]);
});
