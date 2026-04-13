<?php

declare(strict_types=1);

namespace App\Services\Orgs;

use App\Concerns\GeneratesUniqueOrgSlugs;

final class OrgSlugService
{
    use GeneratesUniqueOrgSlugs;

    public function uniqueFromName(string $name, ?int $excludeOrgId = null): string
    {
        return self::generateUniqueOrgSlug($name, $excludeOrgId);
    }
}
