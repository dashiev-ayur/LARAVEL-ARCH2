<?php

namespace App\Http\Requests\Categories;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Org;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderCategoriesRequest extends FormRequest
{
    /**
     * Разрешить reorder пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации пакетного изменения структуры категорий.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->orgId();
        $type = (string) $this->input('type');

        return [
            'type' => ['required', 'string', Rule::in(PostType::values())],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', $type),
            ],
            'items.*.parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', $type),
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
                $type = (string) $this->input('type');

                if (! $orgId || ! in_array($type, PostType::values(), true)) {
                    return;
                }

                /** @var Collection<int, array{id: int|string, parent_id?: int|string|null, sort_order: int|string}> $items */
                $items = collect($this->input('items', []));
                $ids = $items->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add('items', __('The category order contains duplicate categories.'));

                    return;
                }

                /** @var Collection<int, Category> $categories */
                $categories = Category::query()
                    ->where('org_id', $orgId)
                    ->where('type', $type)
                    ->get(['id', 'parent_id']);

                $expectedIds = $categories->pluck('id')->map(fn (int|string $id): int => (int) $id)->sort()->values()->all();
                $payloadIds = collect($ids)->sort()->values()->all();

                if ($expectedIds !== $payloadIds) {
                    $validator->errors()->add('items', __('The category order must include every category of the selected type.'));

                    return;
                }

                $parentById = $categories
                    ->mapWithKeys(fn (Category $category): array => [
                        (int) $category->id => $category->parent_id ? (int) $category->parent_id : null,
                    ])
                    ->all();

                foreach ($items as $index => $item) {
                    $id = (int) $item['id'];
                    $parentId = isset($item['parent_id']) ? (int) $item['parent_id'] : null;

                    if ($parentId === $id) {
                        $validator->errors()->add("items.{$index}.parent_id", __('The selected parent category is invalid.'));

                        return;
                    }

                    $parentById[$id] = $parentId;
                }

                foreach ($items as $index => $item) {
                    $id = (int) $item['id'];
                    $parentId = $parentById[$id] ?? null;
                    $visited = [$id => true];

                    while ($parentId !== null) {
                        if (isset($visited[$parentId])) {
                            $validator->errors()->add("items.{$index}.parent_id", __('The selected parent category is invalid.'));

                            return;
                        }

                        $visited[$parentId] = true;
                        $parentId = $parentById[$parentId] ?? null;
                    }
                }
            },
        ];
    }

    private function orgId(): ?int
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        return is_numeric($orgId) ? (int) $orgId : null;
    }
}
