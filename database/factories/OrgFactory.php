<?php

namespace Database\Factories;

use App\Models\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Org>
 */
class OrgFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'about' => fake()->optional()->paragraph(),
            'logo' => null,
            'website' => fake()->optional()->url(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => null,
            'address' => null,
            'city' => null,
            'status' => null,
        ];
    }
}
