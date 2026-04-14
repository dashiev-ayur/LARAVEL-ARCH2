<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\OrgUpdated;
use App\Models\Org;
use Illuminate\Support\Facades\Log;

class OrgObserver
{
    public function updated(Org $org): void
    {
        /** @var array<string, mixed> $changes */
        $changes = $org->getChanges();

        // unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        /** @var array<string, mixed> $original */
        $original = [];

        foreach (array_keys($changes) as $key) {
            $original[$key] = $org->getOriginal($key);
        }

        // Отладочная информация
        // if (config('app.debug')) {
        //     $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25))
        //         ->map(fn (array $frame): array => [
        //             'file' => $frame['file'] ?? null,
        //             'line' => $frame['line'] ?? null,
        //             'class' => $frame['class'] ?? null,
        //             'function' => $frame['function'] ?? null,
        //         ])
        //         ->filter(fn (array $frame): bool => is_string($frame['file'])
        //             && str_starts_with($frame['file'], base_path('app')))
        //         ->values()
        //         ->take(10)
        //         ->all();

        //     Log::debug('OrgObserver updated() callsite', [
        //         'org_id' => $org->getKey(),
        //         'changes_keys' => array_keys($changes),
        //         'trace' => $trace,
        //     ]);
        // }

        OrgUpdated::dispatch($org, $changes, $original);
    }
}
