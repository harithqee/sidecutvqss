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
        Schema::create('queue_sessions', function (Blueprint $table) {
    $table->id();
    $table->date('session_date')->unique();
    $table->timestamp('opened_at')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->enum('status', ['open', 'closed'])->default('open');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_sessions');
    }
};
