<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Join Confirmation',
                'trigger_event' => 'queue_joined',
                'message_body' => 'Hi {{customerName}}, you have joined the queue at position {{queueNumber}}. Estimated wait: {{waitTime}} minutes.',
            ],
            [
                'name' => 'In-Queue Status Update',
                'trigger_event' => 'queue_status_update',
                'message_body' => 'Hi {{customerName}}, you are currently number {{position}} in the queue.',
            ],
            [
                'name' => 'Service Complete',
                'trigger_event' => 'ticket_completed',
                'message_body' => 'Hi {{customerName}}, thanks for visiting! We hope to see you again soon.',
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template + ['is_active' => true]
            );
        }
    }
}
