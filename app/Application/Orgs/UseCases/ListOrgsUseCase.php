<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Services\Orgs\OrgListingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrgsUseCase
{
    public function __construct(
        private readonly OrgListingService $listing,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): LengthAwarePaginator
    {
        return $this->listing->paginate($validated);
    }
}
