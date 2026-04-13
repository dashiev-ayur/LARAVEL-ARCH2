<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\OrgStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orgs\IndexOrgRequest;
use App\Http\Requests\Orgs\StoreOrgRequest;
use App\Http\Requests\Orgs\UpdateOrgRequest;
use App\Http\Resources\OrgResource;
use App\Models\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class OrgController extends Controller
{
    #[OA\Get(
        path: '/api/orgs',
        summary: 'Список организаций',
        description: 'Постраничный список. По умолчанию сортировка по `id` по убыванию (сначала записи с большим id). Поиск `search` — подстрока в названии (без учёта регистра). Параметры `filter[...]` задают точное совпадение по колонке.',
        tags: ['Организации'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Номер страницы',
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Размер страницы (1–1000), по умолчанию 15',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000, example: 15),
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                description: 'Колонка сортировки',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['id', 'name', 'slug', 'about', 'logo', 'website', 'email', 'phone', 'address', 'city', 'status', 'created_at', 'updated_at'],
                    example: 'id',
                ),
            ),
            new OA\Parameter(
                name: 'direction',
                in: 'query',
                required: false,
                description: 'Направление сортировки (по умолчанию desc)',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc'),
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Подстрока поиска в названии организации (без учёта регистра)',
                schema: new OA\Schema(type: 'string', example: 'Acme'),
            ),
            new OA\Parameter(
                name: 'filter[name]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[slug]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[about]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[logo]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[website]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[email]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'email'),
            ),
            new OA\Parameter(
                name: 'filter[phone]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[address]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[city]',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[status]',
                in: 'query',
                required: false,
                description: 'Точное значение статуса',
                schema: new OA\Schema(type: 'string', enum: ['new', 'enabled', 'deleted']),
            ),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Страница списка организаций',
        content: new OA\JsonContent(
            required: ['data', 'links', 'meta'],
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Org'),
                ),
                new OA\Property(
                    property: 'links',
                    type: 'object',
                    description: 'Ссылки пагинации (first, last, prev, next)',
                ),
                new OA\Property(
                    property: 'meta',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'from', type: 'integer', nullable: true),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'path', type: 'string'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'to', type: 'integer', nullable: true),
                        new OA\Property(property: 'total', type: 'integer'),
                    ],
                ),
            ],
        ),
    )]
    #[OA\Response(response: 422, description: 'Ошибка валидации')]
    public function index(IndexOrgRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Org::query();
        $table = (new Org)->getTable();

        if (! empty($validated['search'])) {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $validated['search']).'%';
            $query->whereRaw($table.'.name ILIKE ? ESCAPE \'\\\'', [$pattern]);
        }

        $filters = $validated['filter'] ?? [];
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($value instanceof OrgStatus) {
                $value = $value->value;
            }

            $query->where((string) $column, $value);
        }

        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        $perPage = $validated['per_page'] ?? 15;

        return OrgResource::collection(
            $query->paginate($perPage)->withQueryString(),
        )->response();
    }

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
    public function update(UpdateOrgRequest $request, Org $org): JsonResponse
    {
        $data = $request->validated();

        $updates = [];

        foreach (['name', 'about', 'logo', 'website', 'email', 'phone', 'address', 'city', 'status'] as $key) {
            if (array_key_exists($key, $data)) {
                $updates[$key] = $data[$key];
            }
        }

        if (array_key_exists('slug', $data)) {
            if ($data['slug'] === null) {
                $nameForSlug = (string) ($updates['name'] ?? $org->name);
                $updates['slug'] = $this->makeUniqueSlugFromName($nameForSlug);
            } else {
                $updates['slug'] = $data['slug'];
            }
        }

        $org->fill($updates);
        $org->save();

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
