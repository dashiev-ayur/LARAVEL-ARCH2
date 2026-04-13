<?php

declare(strict_types=1);

namespace App\Http\Requests\Orgs;

use App\Enums\OrgStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexOrgRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const array SORT_COLUMNS = [
        'id',
        'name',
        'slug',
        'about',
        'logo',
        'website',
        'email',
        'phone',
        'address',
        'city',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @var list<string>
     */
    public const array FILTER_COLUMNS = [
        'name',
        'slug',
        'about',
        'logo',
        'website',
        'email',
        'phone',
        'address',
        'city',
        'status',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'sort' => ['sometimes', 'string', Rule::in(self::SORT_COLUMNS)],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter' => ['sometimes', 'array'],
            'filter.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.about' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'filter.logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.website' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'filter.phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.status' => ['sometimes', 'nullable', Rule::enum(OrgStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $filter = $this->input('filter');
            if (! is_array($filter)) {
                return;
            }

            $allowed = array_flip(self::FILTER_COLUMNS);
            foreach (array_keys($filter) as $key) {
                if (! is_string($key) || ! isset($allowed[$key])) {
                    $validator->errors()->add(
                        'filter',
                        sprintf('Недопустимый ключ фильтра: %s.', is_string($key) ? $key : '—'),
                    );
                }
            }
        });
    }
}
