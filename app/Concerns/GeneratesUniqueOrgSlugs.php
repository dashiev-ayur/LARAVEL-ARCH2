<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Org;
use Illuminate\Support\Str;

trait GeneratesUniqueOrgSlugs
{
    /**
     * Генерирует уникальный slug для организации по имени.
     *
     * Алгоритм:
     * - генерирует базовый slug через {@see Str::slug()}, при пустом результате использует 'org';
     * - проверяет уникальность в БД (для update исключает запись с id = $excludeId);
     * - если slug занят, пробует варианты с числовым postfix: -1, -2, -3 (с проверкой каждого);
     * - если и они заняты, добавляет postfix -<timestamp> и при коллизии инкрементирует timestamp до уникального.
     */
    protected static function generateUniqueOrgSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'org';
        }

        $slugExists = function (string $slug) use ($excludeId): bool {
            $query = Org::query()->where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            return $query->exists();
        };

        if (! $slugExists($baseSlug)) {
            return $baseSlug;
        }

        for ($suffix = 1; $suffix <= 3; $suffix++) {
            $candidate = $baseSlug.'-'.$suffix;

            if (! $slugExists($candidate)) {
                return $candidate;
            }
        }

        $timestamp = now()->timestamp;
        $candidate = $baseSlug.'-'.$timestamp;

        while ($slugExists($candidate)) {
            $timestamp++;
            $candidate = $baseSlug.'-'.$timestamp;
        }

        return $candidate;
    }
}
