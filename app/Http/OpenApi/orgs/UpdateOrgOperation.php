<?php

declare(strict_types=1);

namespace App\Http\OpenApi\orgs;

use OpenApi\Attributes as OA;

#[OA\Patch(
    path: '/api/orgs/{org}',
    summary: 'Обновить организацию (частично)',
    description: 'Частичное обновление организации. Поля являются опциональными: если поле не передано — оно не изменяется. Если поле передано как null — значение очищается (становится null) для nullable-колонок. Для slug: null означает регенерацию slug из name.',
    tags: ['Организации'],
)]
#[OA\Parameter(
    name: 'org',
    in: 'path',
    required: true,
    description: 'Числовой идентификатор организации (первичный ключ)',
    schema: new OA\Schema(type: 'integer', format: 'int64', minimum: 1),
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/OrgUpdateRequest'),
)]
#[OA\Response(
    response: 200,
    description: 'Организация обновлена',
    content: new OA\JsonContent(
        required: ['data'],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Org'),
        ],
    ),
)]
#[OA\Response(response: 404, description: 'Организация с указанным id не найдена')]
#[OA\Response(response: 422, description: 'Ошибка валидации')]
final class UpdateOrgOperation {}
