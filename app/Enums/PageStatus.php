<?php

namespace App\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';

    /**
     * Значения статусов для валидации и UI.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
