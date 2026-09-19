<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('queue_ticket_id')->constrained()->cascadeOnDelete();
    $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
    $table->string('phone');
    $table->text('message_body');
    $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
