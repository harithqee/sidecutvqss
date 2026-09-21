<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\QueueSession;
use App\Models\Barber;
use App\Models\Service;

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

        // Weighted so history (completed/canceled) has plenty of rows to page through
        $status = fake()->randomElement([
            'completed', 'completed', 'completed', 'completed',
            'canceled',
            'serving',
            'in_queue',
        ]);

        $servedAt = null;
        $finishedAt = null;

        // Only 'serving' and 'completed' tickets have been served
        if (in_array($status, ['serving', 'completed'])) {
            $servedAt = fake()->dateTimeBetween($joined, 'now');
        }

        // Only 'completed' tickets have a finish time, and it must be after served_at
        if ($status === 'completed') {
            $finishedAt = fake()->dateTimeBetween($servedAt, 'now');
        }

        return [
            'queue_session_id' => QueueSession::factory(),
            'barber_id' => Barber::factory(),
            'service_id' => Service::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('01#-#######'),
            'queue_number' => fake()->unique()->numberBetween(1, 1000),
            'status' => $status,
            'joined_at' => $joined,
            'served_at' => $servedAt,
            'finished_at' => $finishedAt,
        ];
    }

    /**
     * Ticket that was completed: has both served_at and finished_at.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $joined = $attributes['joined_at'] ?? fake()->dateTimeBetween('-2 hours', 'now');
            $servedAt = fake()->dateTimeBetween($joined, 'now');
            $finishedAt = fake()->dateTimeBetween($servedAt, 'now');

            return [
                'status' => 'completed',
                'joined_at' => $joined,
                'served_at' => $servedAt,
                'finished_at' => $finishedAt,
            ];
        });
    }

    /**
     * Ticket that was canceled before being served.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'served_at' => null,
            'finished_at' => null,
        ]);
    }

    /**
     * Ticket currently being served (no finish time yet).
     */
    public function serving(): static
    {
        return $this->state(function (array $attributes) {
            $joined = $attributes['joined_at'] ?? fake()->dateTimeBetween('-2 hours', 'now');

            return [
                'status' => 'serving',
                'joined_at' => $joined,
                'served_at' => fake()->dateTimeBetween($joined, 'now'),
                'finished_at' => null,
            ];
        });
    }

    /**
     * Ticket still waiting in the queue.
     */
    public function inQueue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_queue',
            'served_at' => null,
            'finished_at' => null,
        ]);
    }
}