<?php

declare(strict_types=1);

namespace App\Http\OpenApi\orgs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/orgs',
    summary: 'Список организаций',
    description: 'Постраничный список. По умолчанию сортировка по `id` по убыванию (сначала записи с большим id). Поиск `search` — подстрока в названии (без учёта регистра). Параметры `filter[...]` задают точное совпадение по колонке.',
    tags: ['Организации'],
    parameters: [
        new OA\Parameter(
            name: 'page',
            in: 'query',
            required: false,
            description: 'Номер страницы',
            schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
        ),
        new OA\Parameter(
            name: 'per_page',
            in: 'query',
            required: false,
            description: 'Размер страницы (1–1000), по умолчанию 15',
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000, example: 15),
        ),
        new OA\Parameter(
            name: 'sort',
            in: 'query',
            required: false,
            description: 'Колонка сортировки',
            schema: new OA\Schema(
                type: 'string',
                enum: ['id', 'name', 'slug', 'about', 'logo', 'website', 'email', 'phone', 'address', 'city', 'status', 'created_at', 'updated_at'],
                example: 'id',
            ),
        ),
        new OA\Parameter(
            name: 'direction',
            in: 'query',
            required: false,
            description: 'Направление сортировки (по умолчанию desc)',
            schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc'),
        ),
        new OA\Parameter(
            name: 'search',
            in: 'query',
            required: false,
            description: 'Подстрока поиска в названии организации (без учёта регистра)',
            schema: new OA\Schema(type: 'string', example: 'Acme'),
        ),
        new OA\Parameter(
            name: 'filter[name]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[slug]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[about]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[logo]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[website]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[email]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string', format: 'email'),
        ),
        new OA\Parameter(
            name: 'filter[phone]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[address]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[city]',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
        new OA\Parameter(
            name: 'filter[status]',
            in: 'query',
            required: false,
            description: 'Точное значение статуса',
            schema: new OA\Schema(type: 'string', enum: ['new', 'enabled', 'deleted']),
        ),
    ],
)]
#[OA\Response(
    response: 200,
    description: 'Страница списка организаций',
    content: new OA\JsonContent(
        required: ['data', 'links', 'meta'],
        properties: [
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/Org'),
            ),
            new OA\Property(
                property: 'links',
                type: 'object',
                description: 'Ссылки пагинации (first, last, prev, next)',
            ),
            new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'from', type: 'integer', nullable: true),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'path', type: 'string'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'to', type: 'integer', nullable: true),
                    new OA\Property(property: 'total', type: 'integer'),
                ],
            ),
        ],
    ),
)]
#[OA\Response(response: 422, description: 'Ошибка валидации')]
final class IndexOrgsOperation {}
