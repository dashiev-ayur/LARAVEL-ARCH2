<?php

namespace App\Enums;

/**
 * Тип записи в каталоге (новость, статья, товар).
 */
enum PostType: string
{
    case News = 'news';
    case Article = 'article';
    case Product = 'product';

    /**
     * Значения для валидации и маршрутов.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
