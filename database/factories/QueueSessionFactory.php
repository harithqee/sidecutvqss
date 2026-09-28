<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QueueSession>
 */
class QueueSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A date is unique at the database level, so make Faker's uniqueness
        // apply to the date itself rather than a timestamp within that date.
        $sessionDates = collect(range(0, 365))
            ->map(fn (int $daysAgo) => today()->subDays($daysAgo)->toDateString())
            ->all();
        $sessionDate = Carbon::parse(fake()->unique()->randomElement($sessionDates))->startOfDay();
        $isClosed = $sessionDate->lt(today());

        return [
            'session_date' => $sessionDate->toDateString(),
            'opened_at' => $sessionDate->copy()->setTime(11, 0),
            'closed_at' => $isClosed ? $sessionDate->copy()->setTime(22, 0) : null,
            'status' => $isClosed ? 'closed' : 'open',
        ];
    }
}
