<?php

namespace App\Http\Requests\Pages;

use App\Enums\PageStatus;
use App\Models\Org;
use App\Services\Pages\PageTreeService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePageRequest extends FormRequest
{
    /**
     * Разрешить создание страницы пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы создания страницы.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->orgId();

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('pages', 'id')->where('org_id', $orgId ?? 0),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'status' => ['required', 'string', Rule::in(PageStatus::values())],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'noindex' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Проверить конфликт итогового URL для явно указанного slug.
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

                $org = $this->org();
                $slug = trim((string) $this->input('slug', ''));

                if (! $org || $slug === '') {
                    return;
                }

                $parentId = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;
                $pageTree = app(PageTreeService::class);

                if ($pageTree->pathExists($org, $pageTree->buildPath($org, $slug, $parentId))) {
                    $validator->errors()->add('slug', __('The page URL already exists.'));
                }
            },
        ];
    }

    private function org(): ?Org
    {
        return Org::query()
            ->where('slug', (string) $this->route('current_org'))
            ->first();
    }

    private function orgId(): ?int
    {
        return $this->org()?->id;
    }
}
