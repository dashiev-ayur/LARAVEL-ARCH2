<?php

namespace App\Http\Requests\Posts;

use App\Enums\PostType;
use App\Models\Org;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    private const ALLOWED_STATUSES = ['draft', 'scheduled', 'published', 'archived'];

    /**
     * Разрешить обновление записи пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы редактирования записи.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        $post = $this->route('post');
        $postId = $post instanceof Post ? $post->getKey() : $post;

        return [
            'type' => ['required', 'string', Rule::in(PostType::values())],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')
                    ->ignore($postId)
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', (string) $this->input('type')),
            ],
            'status' => ['required', 'string', Rule::in(self::ALLOWED_STATUSES)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
