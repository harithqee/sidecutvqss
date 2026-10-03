<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_event');
            $table->text('message_body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('message_templates')->insert([
            [
                'id' => 1,
                'name' => 'Join Confirmation',
                'trigger_event' => 'queue_joined',
                'message_body' => 'Hi {{customerName}}, you have joined the queue at position {{queueNumber}}. Estimated wait: {{waitTime}} minutes.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'In-Queue Status Update',
                'trigger_event' => 'queue_status_update',
                'message_body' => 'Hi {{customerName}}, you are currently number {{position}} in the queue.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Service Complete',
                'trigger_event' => 'ticket_completed',
                'message_body' => 'Hi {{customerName}}, thanks for visiting! We hope to see you again soon.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
