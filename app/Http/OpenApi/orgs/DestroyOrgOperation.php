<?php

declare(strict_types=1);

namespace App\Http\OpenApi\orgs;

use OpenApi\Attributes as OA;

#[OA\Delete(
    path: '/api/orgs/{org}',
    summary: 'Удалить организацию',
    description: 'Удаляет организацию по числовому первичному ключу.',
    tags: ['Организации'],
)]
#[OA\Parameter(
    name: 'org',
    in: 'path',
    required: true,
    description: 'Числовой идентификатор организации (первичный ключ)',
    schema: new OA\Schema(type: 'integer', format: 'int64', minimum: 1),
)]
#[OA\Response(response: 204, description: 'Организация удалена')]
#[OA\Response(response: 404, description: 'Организация с указанным id не найдена')]
final class DestroyOrgOperation {}
