<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Application\Orgs\Ports\OrgListingPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrgsUseCase
{
    public function __construct(
        private readonly OrgListingPort $listing,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): LengthAwarePaginator
    {
        return $this->listing->paginate($validated);
    }
}
