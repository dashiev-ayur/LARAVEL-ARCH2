<?php

declare(strict_types=1);

namespace App\Application\Orgs\UseCases;

use App\Models\Org;
use App\Services\Orgs\OrgWriteService;

final class DeleteOrgUseCase
{
    public function __construct(
        private readonly OrgWriteService $orgs,
    ) {}

    public function execute(Org $org): void
    {
        $this->orgs->delete($org);
    }
}
