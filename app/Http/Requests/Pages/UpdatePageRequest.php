<?php

namespace App\Http\Requests\Pages;

use App\Enums\PageStatus;
use App\Models\Org;
use App\Models\Page;
use App\Services\Pages\PageTreeService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePageRequest extends FormRequest
{
    /**
     * Разрешить обновление страницы пользователю, прошедшему middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации формы редактирования страницы.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->orgId();
        $page = $this->route('page');
        $pageId = $page instanceof Page ? $page->getKey() : $page;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('pages', 'id')
                    ->where('org_id', $orgId ?? 0)
                    ->whereNot('id', $pageId),
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
     * Проверки родителя и итогового URL после базовой валидации.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $page = $this->route('page');

                if (! $page instanceof Page || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $parentId = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;

                if ($parentId && $this->isDescendantOf($page, $parentId)) {
                    $validator->errors()->add('parent_id', __('The selected parent page is invalid.'));

                    return;
                }

                $org = $this->org();
                $slug = trim((string) $this->input('slug', ''));

                if (! $org || $slug === '') {
                    return;
                }

                $pageTree = app(PageTreeService::class);

                if ($pageTree->pathExists($org, $pageTree->buildPath($org, $slug, $parentId), $page)) {
                    $validator->errors()->add('slug', __('The page URL already exists.'));
                }
            },
        ];
    }

    private function isDescendantOf(Page $page, int $parentId): bool
    {
        $descendantIds = Page::query()
            ->where('org_id', $page->org_id)
            ->where('parent_id', $page->id)
            ->pluck('id');

        while ($descendantIds->isNotEmpty()) {
            if ($descendantIds->contains($parentId)) {
                return true;
            }

            $descendantIds = Page::query()
                ->where('org_id', $page->org_id)
                ->whereIn('parent_id', $descendantIds)
                ->pluck('id');
        }

        return false;
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
