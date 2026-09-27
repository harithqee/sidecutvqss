<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QueueTicketSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = config('app.maintenance.timezone', config('app.timezone'));
        $activeBarbers = Barber::query()->where('is_active', true)->get();
        $services = Service::query()->where('is_active', true)->get();

        if ($activeBarbers->isEmpty()) {
            $activeBarbers = collect([Barber::factory()->create(['is_active' => true])]);
        }

        if ($services->isEmpty()) {
            $services = Service::factory()->count(4)->create();
        }

        $today = Carbon::now($timezone)->startOfDay();
        $created = 0;

        // 14 tickets today and 6 for each of the previous six days = 50 samples.
        // Existing rows are kept; rerunning this seeder only fills missing slots.
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $isToday = $daysAgo === 0;
            $session = QueueSession::query()->firstOrCreate(
                ['session_date' => $date->toDateString()],
                [
                    'status' => $isToday ? 'open' : 'closed',
                    'opened_at' => $date->copy()->setTime(11, 0)->utc(),
                    'closed_at' => $isToday ? null : $date->copy()->setTime(22, 0)->utc(),
                ]
            );

            $targetCount = $isToday ? 14 : 6;

            while ($session->tickets()->count() < $targetCount) {
                $position = $session->tickets()->count() + 1;
                $queueNumber = ((int) $session->tickets()->max('queue_number')) + 1;
                $status = $this->statusFor($isToday, $position);
                $service = $services->random();
                $now = Carbon::now($timezone)->utc();

                if ($isToday) {
                    $joinedAt = $now->copy()->subMinutes(max(2, ($targetCount - $position + 1) * 12));
                    $servedAt = $status === 'serving' ? $now->copy()->subMinutes(15) : null;
                    $finishedAt = null;

                    if ($status === 'completed') {
                        $finishedAt = $now->copy()->subMinutes(fake()->numberBetween(1, 25));
                        $servedAt = $finishedAt->copy()->subMinutes($service->duration_minutes);
                        $joinedAt = $servedAt->copy()->subMinutes(fake()->numberBetween(5, 20));
                    } elseif ($status === 'canceled') {
                        $finishedAt = $joinedAt->copy()->addMinutes(fake()->numberBetween(3, 8));
                    }
                } else {
                    $openedAt = $date->copy()->setTime(11, 0)->utc();
                    $joinedAt = $openedAt->copy()->addMinutes(($position - 1) * 75 + fake()->numberBetween(0, 10));
                    $servedAt = $status === 'completed'
                        ? $joinedAt->copy()->addMinutes(fake()->numberBetween(5, 20))
                        : null;
                    $finishedAt = match ($status) {
                        'completed' => $servedAt->copy()->addMinutes($service->duration_minutes),
                        'canceled' => $joinedAt->copy()->addMinutes(fake()->numberBetween(3, 10)),
                        default => null,
                    };
                }

                $isCalling = $isToday && $position === 2 && $status === 'in_queue';

                QueueTicket::factory()->create([
                    'queue_session_id' => $session->id,
                    'barber_id' => $activeBarbers->random()->id,
                    'service_id' => $service->id,
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->numerify('01#-#######'),
                    'queue_number' => $queueNumber,
                    'status' => $status,
                    'is_calling' => $isCalling,
                    'call_version' => $isCalling ? 1 : 0,
                    'joined_at' => $joinedAt,
                    'called_at' => $isCalling ? $now->copy()->subMinutes(2) : null,
                    'served_at' => $servedAt,
                    'finished_at' => $finishedAt,
                ]);

                $created++;
            }

            if ($isToday && !$session->tickets()->where('is_calling', true)->exists()) {
                $nextTicket = $session->tickets()
                    ->where('status', 'in_queue')
                    ->orderBy('queue_number')
                    ->first();

                $nextTicket?->update([
                    'is_calling' => true,
                    'called_at' => Carbon::now($timezone)->utc()->subMinutes(2),
                    'call_version' => max(1, $nextTicket->call_version),
                ]);
            }
        }

        $total = QueueTicket::query()
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $today->copy()->subDays(6)->toDateString(),
                $today->toDateString(),
            ]))
            ->count();

        $this->command?->info("Queue ticket sample ready: {$total} tickets across the last 7 days ({$created} added this run).");
    }

    private function statusFor(bool $isToday, int $position): string
    {
        if (!$isToday) {
            return $position <= 5 ? 'completed' : 'canceled';
        }

        return match (true) {
            $position === 1 => 'serving',
            $position <= 7 => 'in_queue',
            $position <= 12 => 'completed',
            default => 'canceled',
        };
    }
}
