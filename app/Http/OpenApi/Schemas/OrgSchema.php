<?php

declare(strict_types=1);

namespace App\Http\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Org',
    type: 'object',
    required: ['id', 'name', 'slug', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Acme Inc'),
        new OA\Property(property: 'slug', type: 'string', example: 'acme-inc'),
        new OA\Property(property: 'about', type: 'string', nullable: true),
        new OA\Property(property: 'logo', type: 'string', nullable: true),
        new OA\Property(property: 'website', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'city', type: 'string', nullable: true),
        new OA\Property(
            property: 'status',
            type: 'string',
            nullable: true,
            enum: ['new', 'enabled', 'deleted'],
            description: 'Статус организации',
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
final class OrgSchema {}
