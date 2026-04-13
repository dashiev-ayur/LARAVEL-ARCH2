<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Application\Orgs\Ports\OrgWritePort;
use App\Models\Org;

final class DeleteOrgUseCase
{
    public function __construct(
        private readonly OrgWritePort $orgs,
    ) {}

    public function execute(Org $org): void
    {
        $this->orgs->delete($org);
    }
}
