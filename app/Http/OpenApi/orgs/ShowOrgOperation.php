<?php

declare(strict_types=1);

namespace App\Http\OpenApi\orgs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/orgs/{org}',
    summary: 'Получить организацию по идентификатору',
    description: 'Возвращает одну организацию по числовому первичному ключу.',
    tags: ['Организации'],
)]
#[OA\Parameter(
    name: 'org',
    in: 'path',
    required: true,
    description: 'Числовой идентификатор организации (первичный ключ)',
    schema: new OA\Schema(type: 'integer', format: 'int64', minimum: 1),
)]
#[OA\Response(
    response: 200,
    description: 'Организация найдена',
    content: new OA\JsonContent(
        required: ['data'],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Org'),
        ],
    ),
)]
#[OA\Response(response: 404, description: 'Организация с указанным id не найдена')]
final class ShowOrgOperation {}
