<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrgUpdated;
use Illuminate\Support\Facades\Log;

class LogOrgUpdated
{
    public function handle(OrgUpdated $event): void
    {
        Log::info('Org updated', [
            'org_id' => $event->org->getKey(),
            'org_name' => $event->org->name,
            'org_slug' => $event->org->slug,
            'changes' => $event->changes,
            'original' => $event->original,
        ]);
    }
}
