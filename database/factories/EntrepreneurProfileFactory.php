<?php

namespace Database\Factories;

use App\Models\EntrepreneurProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EntrepreneurProfile>
 */
class EntrepreneurProfileFactory extends Factory
{
    public function definition(): array
    {
        $businessName = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'business_name' => $businessName,
            'slug' => Str::slug($businessName),
            'description' => fake()->paragraph(),
            'logo_path' => null,
            'campus_location' => fake()->optional()->city(),
            'is_active' => true,
        ];
    }
}
