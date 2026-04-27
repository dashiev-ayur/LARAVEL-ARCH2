<?php

use App\Enums\PostType;
use App\Models\Org;
use App\Models\Post;
use Database\Seeders\NewsPostSeeder;
use Tests\TestCase;

test('news post seeder creates configured news records', function () {
    /** @var TestCase $this */
    $this->seed(NewsPostSeeder::class);

    expect(Org::query()->whereKey(NewsPostSeeder::ORG_ID)->exists())->toBeTrue()
        ->and(Post::query()
            ->where('org_id', NewsPostSeeder::ORG_ID)
            ->where('type', PostType::News->value)
            ->count()
        )->toBe(NewsPostSeeder::NEWS_COUNT)
        ->and(Post::query()->where('status', 'published')->count())->toBe(NewsPostSeeder::NEWS_COUNT)
        ->and(Post::query()->where('title', 'like', '%Big Tech%')->exists())->toBeTrue()
        ->and(Post::query()->where('type', '!=', PostType::News->value)->count())->toBe(0);
});
