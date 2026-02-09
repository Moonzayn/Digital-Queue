<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('value', 255);
            $table->timestamps();
        });

        // Default settings
        DB::table('settings')->insert([
            ['key' => 'open_hour', 'value' => '08:00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'close_hour', 'value' => '17:00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'slot_duration', 'value' => '15', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'slot_capacity', 'value' => '4', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_name', 'value' => 'Digital Queue', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'is_open', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};