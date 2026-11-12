<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'entrepreneur_profile_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'listing_type' => Listing::TYPE_SALE,
            'availability_type' => Listing::AVAILABILITY_DAILY,
            'status' => Listing::STATUS_AVAILABLE,
            'condition' => null,
            'stock' => null,
            'campus_location' => fake()->optional()->city(),
            'is_active' => true,
            'published_at' => now(),
        ];
    }

    public function exchange(): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_type' => Listing::TYPE_EXCHANGE,
            'price' => null,
        ]);
    }

    public function uniqueAvailability(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability_type' => Listing::AVAILABILITY_UNIQUE,
            'stock' => 1,
        ]);
    }
}
