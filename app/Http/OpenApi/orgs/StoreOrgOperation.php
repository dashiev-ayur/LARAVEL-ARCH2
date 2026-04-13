<?php

declare(strict_types=1);

namespace App\Http\OpenApi\orgs;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/orgs',
    summary: 'Создать организацию',
    description: 'Создаёт новую организацию со статусом `new`. Поле slug необязательно: при отсутствии генерируется из name.',
    tags: ['Организации'],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/OrgStoreRequest'),
)]
#[OA\Response(
    response: 201,
    description: 'Организация создана',
    headers: [
        new OA\Header(
            header: 'Location',
            description: 'URL созданной организации (GET)',
            schema: new OA\Schema(type: 'string', example: '/api/orgs/1'),
        ),
    ],
    content: new OA\JsonContent(
        required: ['data'],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Org'),
        ],
    ),
)]
#[OA\Response(response: 422, description: 'Ошибка валидации')]
final class StoreOrgOperation {}
