<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Org;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class OrgUpdatedProcessingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Org $org)
    {
        $this->onQueue('orgs');
    }

    public function handle(): void
    {
        Log::info('Job OrgUpdatedListener:start', [
            'org_id' => $this->org->getKey(),
            'org_name' => $this->org->name,
        ]);

        sleep(3);

        Log::info('Job OrgUpdatedListener:processing', [
            'org_id' => $this->org->getKey(),
            'org_name' => $this->org->name,
        ]);

        sleep(3);

        Log::info('Job OrgUpdatedListener:success', [
            'org_id' => $this->org->getKey(),
            'org_name' => $this->org->name,
        ]);
    }
}
