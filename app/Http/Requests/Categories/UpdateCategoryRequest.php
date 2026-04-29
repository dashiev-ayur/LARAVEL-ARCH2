<?php

namespace App\Http\Requests\Categories;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Org;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Разрешить обновление категории пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы редактирования категории.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        $category = $this->route('category');
        $categoryId = $category instanceof Category ? $category->getKey() : $category;

        return [
            'type' => ['required', 'string', Rule::in(PostType::values())],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', (string) $this->input('type'))
                    ->whereNot('id', $categoryId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')
                    ->ignore($categoryId)
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', (string) $this->input('type')),
            ],
        ];
    }

    /**
     * Проверки, которым нужны уже провалидированные данные формы.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->route('category');
                $parentId = $this->input('parent_id');

                if (! $category instanceof Category || ! $parentId || $validator->errors()->has('parent_id')) {
                    return;
                }

                if ($this->isDescendantOf($category, (int) $parentId)) {
                    $validator->errors()->add('parent_id', __('The selected parent category is invalid.'));
                }
            },
        ];
    }

    private function isDescendantOf(Category $category, int $parentId): bool
    {
        $descendantIds = Category::query()
            ->where('parent_id', $category->id)
            ->pluck('id');

        while ($descendantIds->isNotEmpty()) {
            if ($descendantIds->contains($parentId)) {
                return true;
            }

            $descendantIds = Category::query()
                ->whereIn('parent_id', $descendantIds)
                ->pluck('id');
        }

        return false;
    }
}
