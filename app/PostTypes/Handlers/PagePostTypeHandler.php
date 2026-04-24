<?php

namespace App\PostTypes\Handlers;

use App\Enums\PostType;
use App\PostTypes\AbstractPostTypeHandler;

final class PagePostTypeHandler extends AbstractPostTypeHandler
{
    public function getType(): PostType
    {
        return PostType::Page;
    }

    public function getFilterButtonTitle(): string
    {
        return 'Страницы';
    }

    public function getNewButtonTitle(): string
    {
        return 'Новая страница';
    }
}
