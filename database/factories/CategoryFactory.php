<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Category;
use App\Models\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(2);

        return [
            'org_id' => Org::factory(),
            'parent_id' => null,
            'type' => fake()->randomElement(PostType::values()),
            'acl_resource' => fake()->optional()->randomElement(['admin', 'news.admin', 'content.editor']),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'title' => $title,
        ];
    }
}
