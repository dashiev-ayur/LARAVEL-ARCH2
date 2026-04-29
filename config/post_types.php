<?php

use App\PostTypes\Handlers\ArticlePostTypeHandler;
use App\PostTypes\Handlers\NewsPostTypeHandler;
use App\PostTypes\Handlers\ProductPostTypeHandler;

return [

    /*
    | Класс handler на каждое значение `App\Enums\PostType` (код типа = ключ).
    */
    'handlers' => [
        'news' => NewsPostTypeHandler::class,
        'article' => ArticlePostTypeHandler::class,
        'product' => ProductPostTypeHandler::class,
    ],

];
