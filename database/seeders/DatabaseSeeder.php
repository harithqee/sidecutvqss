<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Barber;
use App\Models\MessageTemplate;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $lindsey = Barber::firstOrCreate(
            ['name' => 'Lindsey Curtis'],
            ['role' => 'Senior Barber', 'is_active' => true]
        );

        $danial = Barber::firstOrCreate(
            ['name' => 'Danial'],
            ['role' => 'Junior Barber', 'is_active' => false]
        );

        MessageTemplate::firstOrCreate(
            ['name' => 'Almost Ready'],
            ['trigger_event' => 'queue_almost_ready', 'message_body' => 'You\'re up next, please head to the shop.', 'is_active' => true]
        );

        MessageTemplate::firstOrCreate(
            ['name' => 'Service Complete'],
            ['trigger_event' => 'ticket_completed', 'message_body' => 'Thanks for visiting! See you next time.', 'is_active' => true]
        );

        $session = QueueSession::firstOrCreate(
            ['session_date' => today()],
            ['status' => 'open', 'opened_at' => now()]
        );

        // Active tickets — currently in the live queue
        QueueTicket::firstOrCreate(
            ['queue_session_id' => $session->id, 'queue_number' => 1],
            [
                'barber_id' => $lindsey->id,
                'customer_name' => 'Kaiya George',
                'customer_phone' => '012-3456789',
                'status' => 'serving',
                'joined_at' => now()->subMinutes(20),
                'served_at' => now()->subMinutes(5),
            ]
        );

        QueueTicket::firstOrCreate(
            ['queue_session_id' => $session->id, 'queue_number' => 2],
            [
                'barber_id' => $danial->id,
                'customer_name' => 'Zain Geidt',
                'customer_phone' => '019-8887766',
                'status' => 'in_queue',
                'joined_at' => now()->subMinutes(10),
            ]
        );

        QueueTicket::firstOrCreate(
            ['queue_session_id' => $session->id, 'queue_number' => 3],
            [
                'barber_id' => null,
                'customer_name' => 'Abram Schleifer',
                'customer_phone' => '017-2223344',
                'status' => 'in_queue',
                'joined_at' => now()->subMinutes(3),
            ]
        );

        // Finished tickets — for Queue History
        QueueTicket::firstOrCreate(
            ['queue_session_id' => $session->id, 'queue_number' => 4],
            [
                'barber_id' => $lindsey->id,
                'customer_name' => 'Carla George',
                'customer_phone' => '013-5556677',
                'status' => 'completed',
                'joined_at' => now()->subHours(2),
                'served_at' => now()->subHours(2)->addMinutes(25),
                'finished_at' => now()->subHours(1)->subMinutes(35),
            ]
        );

        QueueTicket::firstOrCreate(
            ['queue_session_id' => $session->id, 'queue_number' => 5],
            [
                'barber_id' => $danial->id,
                'customer_name' => 'Marcus Tan',
                'customer_phone' => '011-9998877',
                'status' => 'canceled',
                'joined_at' => now()->subHours(3),
            ]
        );

        $this->call(QueueTicketSeeder::class);
        
    }
}
