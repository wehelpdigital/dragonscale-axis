<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE as_chat_messages MODIFY COLUMN senderType ENUM('visitor', 'admin', 'system') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE as_chat_messages MODIFY COLUMN senderType ENUM('visitor', 'admin') NOT NULL");
    }
};
