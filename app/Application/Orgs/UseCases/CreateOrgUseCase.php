<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Application\Orgs\Ports\OrgWritePort;
use App\Models\Org;

final class CreateOrgUseCase
{
    public function __construct(
        private readonly OrgWritePort $orgs,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): Org
    {
        return $this->orgs->create($validated);
    }
}
