<?php

namespace App\Http\Requests\Pages;

use App\Models\Org;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderPagesRequest extends FormRequest
{
    /**
     * Разрешить reorder пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации пакетного изменения структуры страниц.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->orgId();

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('pages', 'id')->where('org_id', $orgId ?? 0),
            ],
            'items.*.parent_id' => [
                'nullable',
                'integer',
                Rule::exists('pages', 'id')->where('org_id', $orgId ?? 0),
            ],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Проверки целостности дерева после базовой валидации.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $orgId = $this->orgId();

                if (! $orgId) {
                    return;
                }

                /** @var Collection<int, array{id: int|string, parent_id?: int|string|null, sort_order: int|string}> $items */
                $items = collect($this->input('items', []));
                $ids = $items->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add('items', __('The page order contains duplicate pages.'));

                    return;
                }

                /** @var Collection<int, Page> $pages */
                $pages = Page::query()
                    ->where('org_id', $orgId)
                    ->get(['id', 'parent_id', 'slug']);

                $expectedIds = $pages->pluck('id')->map(fn (int|string $id): int => (int) $id)->sort()->values()->all();
                $payloadIds = collect($ids)->sort()->values()->all();

                if ($expectedIds !== $payloadIds) {
                    $validator->errors()->add('items', __('The page order must include every page of the current organization.'));

                    return;
                }

                $parentById = $pages
                    ->mapWithKeys(fn (Page $page): array => [
                        (int) $page->id => $page->parent_id ? (int) $page->parent_id : null,
                    ])
                    ->all();

                foreach ($items as $index => $item) {
                    $id = (int) $item['id'];
                    $parentId = isset($item['parent_id']) ? (int) $item['parent_id'] : null;

                    if ($parentId === $id) {
                        $validator->errors()->add("items.{$index}.parent_id", __('The selected parent page is invalid.'));

                        return;
                    }

                    $parentById[$id] = $parentId;
                }

                if (! $this->validateNoCycles($items, $parentById, $validator)) {
                    return;
                }

                $this->validateUniqueProjectedPaths($pages, $parentById, $validator);
            },
        ];
    }

    /**
     * @param  Collection<int, array{id: int|string, parent_id?: int|string|null, sort_order: int|string}>  $items
     * @param  array<int, int|null>  $parentById
     */
    private function validateNoCycles(Collection $items, array $parentById, Validator $validator): bool
    {
        foreach ($items as $index => $item) {
            $id = (int) $item['id'];
            $parentId = $parentById[$id] ?? null;
            $visited = [$id => true];

            while ($parentId !== null) {
                if (isset($visited[$parentId])) {
                    $validator->errors()->add("items.{$index}.parent_id", __('The selected parent page is invalid.'));

                    return false;
                }

                $visited[$parentId] = true;
                $parentId = $parentById[$parentId] ?? null;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  array<int, int|null>  $parentById
     */
    private function validateUniqueProjectedPaths(Collection $pages, array $parentById, Validator $validator): void
    {
        $slugById = $pages
            ->mapWithKeys(fn (Page $page): array => [(int) $page->id => $page->slug])
            ->all();
        $pathById = [];
        $seenPaths = [];

        foreach ($slugById as $id => $slug) {
            $path = $this->projectPath((int) $id, $slugById, $parentById, $pathById);

            if (isset($seenPaths[$path])) {
                $validator->errors()->add('items', __('The page order creates duplicate page URLs.'));

                return;
            }

            $seenPaths[$path] = true;
        }
    }

    /**
     * @param  array<int, string>  $slugById
     * @param  array<int, int|null>  $parentById
     * @param  array<int, string>  $pathById
     */
    private function projectPath(int $id, array $slugById, array $parentById, array &$pathById): string
    {
        if (isset($pathById[$id])) {
            return $pathById[$id];
        }

        $parentId = $parentById[$id] ?? null;
        $slug = $slugById[$id];

        if ($parentId === null) {
            return $pathById[$id] = $slug;
        }

        return $pathById[$id] = $this->projectPath($parentId, $slugById, $parentById, $pathById).'/'.$slug;
    }

    private function orgId(): ?int
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        return is_numeric($orgId) ? (int) $orgId : null;
    }
}
