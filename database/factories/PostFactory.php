<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Org;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'org_id' => Org::factory(),
            'author_id' => User::factory(),
            'parent_id' => null,
            'type' => fake()->randomElement(PostType::values()),
            'status' => fake()->randomElement(['draft', 'scheduled', 'published', 'archived']),
            'acl_resource' => fake()->optional()->randomElement(['admin', 'news.admin', 'content.editor']),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'title' => $title,
            'excerpt' => fake()->optional()->paragraph(),
            'content' => fake()->optional()->paragraphs(3, true),
            'published_at' => fake()->optional()->dateTimeBetween('-1 month', '+1 month'),
        ];
    }
}
