<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\OrgStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orgs\StoreOrgRequest;
use App\Http\Resources\OrgResource;
use App\Models\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class OrgController extends Controller
{
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
    public function store(StoreOrgRequest $request): JsonResponse
    {
        $data = $request->validated();
        $name = $data['name'];
        $slug = $data['slug'] ?? $this->makeUniqueSlugFromName($name);

        $org = Org::create([
            'name' => $name,
            'slug' => $slug,
            'about' => $data['about'] ?? null,
            'logo' => $data['logo'] ?? null,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'status' => OrgStatus::New,
        ]);

        return (new OrgResource($org))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('orgs.show', $org, absolute: false));
    }

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
    public function show(Org $org): JsonResponse
    {
        return (new OrgResource($org))->response();
    }

    private function makeUniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'org';
        }

        $slug = $base;
        $suffix = 2;
        while (Org::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
