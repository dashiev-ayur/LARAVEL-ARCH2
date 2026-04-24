<?php

namespace App\PostTypes\Handlers;

use App\Enums\PostType;
use App\PostTypes\AbstractPostTypeHandler;

final class ProductPostTypeHandler extends AbstractPostTypeHandler
{
    public function getType(): PostType
    {
        return PostType::Product;
    }

    public function getFilterButtonTitle(): string
    {
        return 'Товары';
    }

    public function getNewButtonTitle(): string
    {
        return 'Новый товар';
    }
}
