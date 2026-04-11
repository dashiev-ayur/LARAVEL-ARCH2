<?php

use App\Enums\OrgStatus;
use App\Models\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('у организации по умолчанию status равен null, после установки приводится к enum', function () {
    $org = Org::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    expect($org->fresh()->status)->toBeNull();

    $org->update(['status' => OrgStatus::Enabled]);

    expect($org->fresh()->status)->toBe(OrgStatus::Enabled);
});

test('status организации принимает все значения enum при чтении из БД', function () {
    foreach (OrgStatus::cases() as $status) {
        $org = Org::create([
            'name' => 'Org '.$status->value,
            'slug' => 'org-'.$status->value,
            'status' => $status,
        ]);

        expect($org->fresh()->status)->toBe($status);
    }
});
