<?php

namespace App\PostTypes\Handlers;

use App\Enums\PostType;
use App\PostTypes\AbstractPostTypeHandler;

final class NewsPostTypeHandler extends AbstractPostTypeHandler
{
    public function getType(): PostType
    {
        return PostType::News;
    }

    public function getFilterButtonTitle(): string
    {
        return 'Новости';
    }

    public function getNewButtonTitle(): string
    {
        return 'Новая новость';
    }
}
