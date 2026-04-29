<?php

namespace App\Http\Requests\Posts;

use App\Enums\PostType;
use App\Models\Org;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostCategoriesRequest extends FormRequest
{
    /**
     * Разрешить обновление связей пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации списка категорий записи.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        $post = $this->route('post');
        $postType = $post instanceof Post
            ? ($post->type instanceof PostType ? $post->type->value : (string) $post->type)
            : '';

        return [
            'category_ids' => ['present', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', $postType),
            ],
        ];
    }
}
