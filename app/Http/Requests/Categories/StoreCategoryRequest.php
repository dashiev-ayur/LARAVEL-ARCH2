<?php

namespace App\Http\Requests\Categories;

use App\Enums\PostType;
use App\Models\Org;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Разрешить создание категории пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы создания категории.
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', (string) $this->input('type')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')
                    ->where('org_id', $orgId ?? 0)
                    ->where('type', (string) $this->input('type')),
            ],
        ];
    }
}
