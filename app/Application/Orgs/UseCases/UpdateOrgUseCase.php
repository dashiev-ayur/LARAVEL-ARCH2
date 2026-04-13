<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Models\Org;
use App\Services\Orgs\OrgWriteService;

final class UpdateOrgUseCase
{
    public function __construct(
        private readonly OrgWriteService $orgs,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Org $org, array $validated): Org
    {
        return $this->orgs->update($org, $validated);
    }
}
