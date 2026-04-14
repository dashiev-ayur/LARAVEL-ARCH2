<?php

declare(strict_types=1);

use App\Events\OrgUpdated;
use App\Listeners\LogOrgUpdated;
use App\Models\Org;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

test('при обновлении Org диспатчится событие OrgUpdated (через observer)', function () {
    Event::fake([
        OrgUpdated::class,
    ]);

    $org = Org::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-slug',
    ]);

    $org->update([
        'name' => 'New Name',
    ]);

    Event::assertDispatched(OrgUpdated::class, function (OrgUpdated $event) use ($org): bool {
        expect($event->org->is($org))->toBeTrue();

        expect($event->changes)->toMatchArray([
            'name' => 'New Name',
        ]);

        expect($event->original)->toMatchArray([
            'name' => 'Old Name',
        ]);

        return true;
    });
});

test('listener пишет в лог при OrgUpdated', function () {
    Log::spy();

    $org = Org::factory()->make([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    (new LogOrgUpdated)->handle(new OrgUpdated(
        org: $org,
        changes: ['name' => 'New Name'],
        original: ['name' => 'Old Name'],
    ));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($org): bool {
            expect($message)->toBe('Org updated');

            expect($context)->toHaveKeys([
                'org_id',
                'org_name',
                'org_slug',
                'changes',
                'original',
            ]);

            expect($context['org_id'])->toBe($org->getKey());
            expect($context['org_name'])->toBe('Acme');
            expect($context['org_slug'])->toBe('acme');
            expect($context['changes'])->toBe(['name' => 'New Name']);
            expect($context['original'])->toBe(['name' => 'Old Name']);

            return true;
        });
});

test('OrgUpdated вызывает listener ровно один раз', function () {
    Log::spy();

    $org = Org::factory()->create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    OrgUpdated::dispatch(
        org: $org,
        changes: ['name' => 'New Name'],
        original: ['name' => 'Old Name'],
    );

    Log::shouldHaveReceived('info')->once();
});
