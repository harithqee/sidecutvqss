<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = fake()->randomElement([
            ['name' => 'Haircut', 'duration_minutes' => 25, 'price' => 35],
            ['name' => 'Beard Trim', 'duration_minutes' => 15, 'price' => 20],
            ['name' => 'Haircut & Beard Trim', 'duration_minutes' => 40, 'price' => 50],
            ['name' => 'Kids Haircut', 'duration_minutes' => 20, 'price' => 25],
        ]);

        return $service + ['is_active' => true];
    }
}
