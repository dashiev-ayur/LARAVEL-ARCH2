<?php

declare(strict_types=1);

namespace App\Http\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrgStoreRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Acme Inc'),
        new OA\Property(
            property: 'slug',
            type: 'string',
            maxLength: 255,
            nullable: true,
            description: 'Уникальный slug; если не указан, будет сгенерирован из name',
            example: 'acme-inc',
        ),
        new OA\Property(property: 'about', type: 'string', nullable: true),
        new OA\Property(property: 'logo', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'website', type: 'string', format: 'uri', maxLength: 255, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'phone', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'address', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'city', type: 'string', maxLength: 255, nullable: true),
    ],
)]
final class OrgStoreRequestSchema {}
