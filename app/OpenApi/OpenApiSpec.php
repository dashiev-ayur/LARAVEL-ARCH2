<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'L5 Swagger UI',
    version: '1.0.0',
    description: 'Интерактивная документация HTTP API',
)]
#[OA\Server(
    url: '/',
    description: 'Текущий хост приложения',
)]
final class OpenApiSpec
{
    /**
     * Заглушка для swagger-php: маршрут в приложении можно добавить позже.
     */
    #[OA\Get(
        path: '/api/health',
        summary: 'Проверка доступности API',
        tags: ['Служебное'],
    )]
    #[OA\Response(response: 200, description: 'Сервис отвечает')]
    public function openApiHealthPlaceholder(): void {}
}
