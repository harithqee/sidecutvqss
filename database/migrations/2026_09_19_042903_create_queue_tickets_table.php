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
        Schema::create('queue_tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('queue_session_id')->constrained()->cascadeOnDelete();
    $table->foreignId('barber_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

    $table->string('customer_name');
    $table->string('customer_phone');
    $table->unsignedInteger('queue_number'); // sequential per session, drives '#1', '#2'...

    $table->enum('status', ['in_queue', 'serving', 'completed', 'canceled'])
          ->default('in_queue');

    $table->timestamp('joined_at')->useCurrent();
    $table->timestamp('served_at')->nullable();
    $table->timestamp('finished_at')->nullable();

    $table->timestamps();

    $table->unique(['queue_session_id', 'queue_number']);
    $table->index('status');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
