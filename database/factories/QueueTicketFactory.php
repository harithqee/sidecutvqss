<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QueueTicket>
 */
class QueueTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    $joined = fake()->dateTimeBetween('-2 hours', 'now');

    return [
        'queue_session_id' => QueueSession::factory(),
        'barber_id' => Barber::factory(),
        'service_id' => Service::factory(),
        'customer_name' => fake()->name(),
        'customer_phone' => fake()->numerify('01#-#######'),
        'queue_number' => fake()->unique()->numberBetween(1, 200),
        'status' => fake()->randomElement(['in_queue', 'serving', 'completed', 'canceled']),
        'joined_at' => $joined,
        'served_at' => fake()->optional(0.7)->dateTimeBetween($joined, 'now'),
        'finished_at' => null,
    ];
}
}
