<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QueueSession;
use App\Models\Barber;
use App\Models\Service;
use App\Models\QueueTicket;

class QueueTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates a handful of sessions spread over the last 10 days, a small
     * pool of barbers/services, then 100 tickets distributed across them —
     * enough to exercise both the date-range filter and pagination.
     */
    public function run(): void
    {
        // Reuse a small pool of barbers and services instead of one per ticket
        $barbers = Barber::factory()->count(5)->create();
        $services = Service::factory()->count(4)->create();

        // One session per day for the last 10 days
        $sessions = collect(range(0, 9))->map(function (int $daysAgo) {
            return QueueSession::factory()->create([
                'session_date' => now()->subDays($daysAgo)->toDateString(),
            ]);
        });

        // 100 tickets, spread across the sessions/barbers/services above
        QueueTicket::factory()
            ->count(100)
            ->make()
            ->each(function (QueueTicket $ticket) use ($sessions, $barbers, $services) {
                $ticket->queue_session_id = $sessions->random()->id;
                $ticket->barber_id = $barbers->random()->id;
                $ticket->service_id = $services->random()->id;
                $ticket->save();
            });
    }
}