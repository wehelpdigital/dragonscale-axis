<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_chat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settingKey', 100)->unique();
            $table->text('settingValue')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        \DB::table('as_chat_settings')->insert([
            [
                'settingKey' => 'auto_reply_enabled',
                'settingValue' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'settingKey' => 'auto_reply_message',
                'settingValue' => 'Salamat sa iyong mensahe! Wala pang available na admin ngayon, pero babalikan ka namin sa lalong madaling panahon.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('as_chat_settings');
    }
};
