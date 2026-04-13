<?php

declare(strict_types=1);

namespace App\Services\Orgs;

use App\Enums\OrgStatus;
use App\Http\Requests\Orgs\IndexOrgRequest;
use App\Models\Org;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrgListingService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function paginate(array $validated): LengthAwarePaginator
    {
        $query = Org::query();
        $table = (new Org)->getTable();

        if (! empty($validated['search'])) {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $validated['search']).'%';
            $query->whereRaw($table.'.name ILIKE ? ESCAPE \'\\\'', [$pattern]);
        }

        $filters = $validated['filter'] ?? [];
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($value instanceof OrgStatus) {
                $value = $value->value;
            }

            $query->where((string) $column, $value);
        }

        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'desc';

        if (! in_array($sort, IndexOrgRequest::SORT_COLUMNS, true)) {
            $sort = 'id';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        $perPage = $validated['per_page'] ?? 15;

        return $query->paginate($perPage)->withQueryString();
    }
}
