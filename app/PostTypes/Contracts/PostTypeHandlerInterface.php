<?php

namespace App\PostTypes\Contracts;

use App\Enums\PostType;
use App\PostTypes\Data\PostTypeUiData;

/**
 * Поведение и подписи UI для одного `PostType`.
 */
interface PostTypeHandlerInterface
{
    public function getType(): PostType;

    public function getFilterButtonTitle(): string;

    public function getNewButtonTitle(): string;

    public function toData(): PostTypeUiData;
}
