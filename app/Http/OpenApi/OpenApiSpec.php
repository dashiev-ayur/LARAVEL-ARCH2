<?php

declare(strict_types=1);

namespace App\Http\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Корневые метаданные OpenAPI для swagger-php (сканируется вместе с `app/`).
 */
#[OA\Info(
    title: 'L5 Swagger UI',
    version: '1.0.0',
    description: 'Интерактивная документация HTTP API',
)]
#[OA\Server(
    url: '/',
    description: 'Текущий хост приложения',
)]
final class OpenApiSpec {}
