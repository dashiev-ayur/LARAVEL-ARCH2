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

class OrgController extends Controller
{
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

    public function show(Org $org): JsonResponse
    {
        return (new OrgResource($org))->response();
    }

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
