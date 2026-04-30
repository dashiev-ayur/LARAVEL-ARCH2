<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Org;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(2);
        $slug = Str::slug($title).'-'.fake()->unique()->numerify('####');

        return [
            'org_id' => Org::factory(),
            'author_id' => null,
            'parent_id' => null,
            'reviewer_id' => null,
            'status' => PageStatus::Draft,
            'acl_resource' => null,
            'sort_order' => 0,
            'slug' => $slug,
            'path' => $slug,
            'depth' => 0,
            'title' => $title,
            'excerpt' => null,
            'content' => null,
            'template' => null,
            'seo_title' => null,
            'meta_description' => null,
            'noindex' => false,
            'content_hash' => null,
            'generated_hash' => null,
            'generated_at' => null,
            'needs_generation' => true,
            'published_at' => null,
            'reviewed_at' => null,
        ];
    }
}
