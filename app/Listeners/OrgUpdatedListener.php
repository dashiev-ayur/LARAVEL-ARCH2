<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrgUpdated;
use App\Jobs\OrgUpdatedProcessingJob;

class OrgUpdatedListener
{
    public function handle(OrgUpdated $event): void
    {
        // просто передаем job в очередь
        OrgUpdatedProcessingJob::dispatch($event->org);
    }
}
