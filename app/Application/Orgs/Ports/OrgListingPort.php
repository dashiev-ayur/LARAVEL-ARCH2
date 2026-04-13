<?php

declare(strict_types=1);

namespace App\Application\Orgs\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrgListingPort
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function paginate(array $validated): LengthAwarePaginator;
}
