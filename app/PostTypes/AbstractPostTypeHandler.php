<?php

namespace App\PostTypes;

use App\PostTypes\Contracts\PostTypeHandlerInterface;
use App\PostTypes\Data\PostTypeUiData;

/**
 * Собирает DTO; конкретные типы реализуют `getType()` и заголовки.
 */
abstract class AbstractPostTypeHandler implements PostTypeHandlerInterface
{
    public function toData(): PostTypeUiData
    {
        return new PostTypeUiData(
            $this->getType(),
            $this->getFilterButtonTitle(),
            $this->getNewButtonTitle(),
        );
    }
}
