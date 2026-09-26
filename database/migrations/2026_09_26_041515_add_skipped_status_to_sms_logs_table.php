<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sms_logs MODIFY status ENUM('pending','sent','failed','skipped') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sms_logs MODIFY status ENUM('pending','sent','failed') DEFAULT 'pending'");
    }
};