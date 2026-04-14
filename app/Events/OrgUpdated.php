<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Org;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrgUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $original
     */
    public function __construct(
        public Org $org,
        public array $changes,
        public array $original,
    ) {}
}
