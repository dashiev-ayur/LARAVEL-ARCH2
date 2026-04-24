<?php

namespace App\PostTypes\Handlers;

use App\Enums\PostType;
use App\PostTypes\AbstractPostTypeHandler;

final class ArticlePostTypeHandler extends AbstractPostTypeHandler
{
    public function getType(): PostType
    {
        return PostType::Article;
    }

    public function getFilterButtonTitle(): string
    {
        return 'Статьи';
    }

    public function getNewButtonTitle(): string
    {
        return 'Новая статья';
    }
}
