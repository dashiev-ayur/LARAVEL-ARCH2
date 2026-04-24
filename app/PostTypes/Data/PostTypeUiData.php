<?php

namespace App\PostTypes\Data;

use App\Enums\PostType;

/**
 * DTO подписей для Inertia/JSON (без дублирования на фронте).
 */
final readonly class PostTypeUiData
{
    public function __construct(
        public PostType $type,
        public string $filterButtonTitle,
        public string $newButtonTitle,
    ) {}

    /**
     * @return array{filterButtonTitle: string, newButtonTitle: string}
     */
    public function toInertiaArray(): array
    {
        return [
            'filterButtonTitle' => $this->filterButtonTitle,
            'newButtonTitle' => $this->newButtonTitle,
        ];
    }
}
