<?php

namespace App\Http\Requests\Posts;

use App\Enums\PostType;
use App\Models\Org;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    private const ALLOWED_STATUSES = ['draft', 'scheduled', 'published', 'archived'];

    /**
     * Разрешить создание записи пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы создания записи.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->value('id');

        return [
            'type' => ['required', 'string', Rule::in(PostType::values())],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')
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
