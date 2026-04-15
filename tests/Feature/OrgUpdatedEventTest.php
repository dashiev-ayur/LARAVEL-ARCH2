<?php

declare(strict_types=1);

use App\Events\OrgUpdated;
use App\Jobs\OrgUpdatedProcessingJob;
use App\Listeners\LogOrgUpdated1;
use App\Listeners\OrgUpdatedListener;
use App\Models\Org;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

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

    (new LogOrgUpdated1)->handle(new OrgUpdated(
        org: $org,
        changes: ['name' => 'New Name'],
        original: ['name' => 'Old Name'],
    ));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($org): bool {
            expect($message)->toBe('Org updated 1');

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

test('OrgUpdatedProcessingJob пишет в лог те же сообщения что раньше писал handle слушателя', function () {
    Log::spy();

    $org = Org::factory()->create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    (new OrgUpdatedProcessingJob($org))->handle();

    Log::shouldHaveReceived('info')->times(3);

    Log::shouldHaveReceived('info')->with('Job OrgUpdatedListener:start', [
        'org_id' => $org->getKey(),
        'org_name' => 'Acme',
    ]);

    Log::shouldHaveReceived('info')->with('Job OrgUpdatedListener:processing', [
        'org_id' => $org->getKey(),
        'org_name' => 'Acme',
    ]);

    Log::shouldHaveReceived('info')->with('Job OrgUpdatedListener:success', [
        'org_id' => $org->getKey(),
        'org_name' => 'Acme',
    ]);
});

test('OrgUpdatedListener ставит OrgUpdatedProcessingJob в очередь orgs', function () {
    Queue::fake();

    $org = Org::factory()->create();

    (new OrgUpdatedListener)->handle(new OrgUpdated(
        org: $org,
        changes: [],
        original: [],
    ));

    Queue::assertPushedOn('orgs', OrgUpdatedProcessingJob::class, function ($job, $queue) use ($org): bool {
        return $job instanceof OrgUpdatedProcessingJob
            && $queue === 'orgs'
            && $job->org->is($org);
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

    Log::shouldHaveReceived('info')->with('Org updated 1', Mockery::type('array'));
    Log::shouldHaveReceived('info')->with('Org updated 2', Mockery::type('array'));
});
