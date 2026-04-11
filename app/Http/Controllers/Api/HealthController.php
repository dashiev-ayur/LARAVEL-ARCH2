<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    #[OA\Get(
        path: '/api/health',
        summary: 'Проверка доступности API',
        description: 'Лёгкая проверка: сервер отвечает и отдаёт JSON. Зависимости (БД, очереди) не проверяются.',
        tags: ['Служебное'],
    )]
    #[OA\Response(
        response: 200,
        description: 'API доступен',
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'Индикатор работоспособности',
                    type: 'string',
                    example: 'ok',
                ),
            ],
        ),
    )]
    public function index(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function store(Request $request): never
    {
        abort(405);
    }

    public function show(string $health): never
    {
        abort(405);
    }

    public function update(Request $request, string $health): never
    {
        abort(405);
    }

    public function destroy(string $health): never
    {
        abort(405);
    }
}
