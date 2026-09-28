<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\QueueSession;
use App\Models\Barber;
use Carbon\Carbon;

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
        $now = Carbon::now();
        $joined = $now->copy()->subMinutes(fake()->numberBetween(0, 180));

        return [
            'queue_session_id' => fn () => QueueSession::today()->getKey(),
            // Assign an existing active barber when available; never create
            // a barber as a side effect of making a queue ticket.
            'barber_id' => fn () => Barber::query()
                ->where('is_active', true)
                ->inRandomOrder()
                ->value('id'),
            'service_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('01#-#######'),
            // Three-digit ticket numbers, matching the public queue display.
            'queue_number' => fake()->unique()->numberBetween(100, 999),
            'status' => 'in_queue',
            'is_calling' => false,
            'joined_at' => $joined,
            'called_at' => null,
            'call_version' => 0,
            'served_at' => null,
            'finished_at' => null,
        ];
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
            'is_calling' => false,
            'called_at' => null,
            'call_version' => 0,
        ]);
    }

    /** Waiting ticket currently announced on the public calling board. */
    public function called(): static
    {
        return $this->inQueue()->state(fn () => [
            'is_calling' => true,
            'called_at' => now()->subMinutes(fake()->numberBetween(0, 5)),
            'call_version' => 1,
        ]);
    }

    /**
     * Completed ticket with today's timestamps, useful for testing daily stats.
     */
    public function completedToday(): static
    {
        return $this->state(function (): array {
            $todayStart = Carbon::today();
            $elapsedMinutes = max(0, (int) $todayStart->diffInMinutes(now()));
            $serviceMinutes = min(fake()->numberBetween(10, 45), $elapsedMinutes);
            $waitMinutes = min(fake()->numberBetween(5, 25), max(0, $elapsedMinutes - $serviceMinutes));
            $availableOffset = max(0, $elapsedMinutes - $serviceMinutes - $waitMinutes);
            $finishedAt = now()->subMinutes(fake()->numberBetween(0, min(180, $availableOffset)));
            $servedAt = $finishedAt->copy()->subMinutes($serviceMinutes);
            $joinedAt = $servedAt->copy()->subMinutes($waitMinutes);

            return [
                'status' => 'completed',
                'is_calling' => false,
                'called_at' => null,
                'served_at' => $servedAt,
                'finished_at' => $finishedAt,
                'joined_at' => $joinedAt,
            ];
        });
    }
}
