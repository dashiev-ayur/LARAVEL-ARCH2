<?php

declare(strict_types=1);

namespace App\Application\Orgs\Ports;

use App\Models\Org;

interface OrgWritePort
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): Org;

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Org $org, array $validated): Org;

    public function delete(Org $org): void;
}
