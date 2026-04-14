<?php

declare(strict_types=1);

namespace App\Services\Orgs;

use App\Application\Orgs\Ports\OrgWritePort;
use App\Enums\OrgStatus;
use App\Models\Org;

final class OrgWriteService implements OrgWritePort
{
    public function __construct(
        private readonly OrgSlugService $slugs,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): Org
    {
        $name = $validated['name'];
        $slug = $validated['slug'] ?? $this->slugs->uniqueFromName($name);

        return Org::create([
            'name' => $name,
            'slug' => $slug,
            'about' => $validated['about'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'website' => $validated['website'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => OrgStatus::New,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Org $org, array $validated): Org
    {
        $updates = [];

        foreach (['name', 'about', 'logo', 'website', 'email', 'phone', 'address', 'city', 'status'] as $key) {
            if (array_key_exists($key, $validated)) {
                $updates[$key] = $validated[$key];
            }
        }

        if (array_key_exists('slug', $validated)) {
            if ($validated['slug'] === null) {
                $nameForSlug = (string) ($updates['name'] ?? $org->name);
                $updates['slug'] = $this->slugs->uniqueFromName($nameForSlug, $org->id);
            } else {
                $updates['slug'] = $validated['slug'];
            }
        }

        $org->fill($updates);
        // $org->touch();
        // $org->forceFill(['updated_at' => now()]); // Это вызовет lifecycle-хук updated и диспатчинг события OrgUpdated
        $org->save();

        return $org;
    }

    public function delete(Org $org): void
    {
        $org->delete();
    }
}
