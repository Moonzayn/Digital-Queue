<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 10);
            $table->string('unique_code', 64)->unique();
            $table->enum('type', ['walk-in', 'online'])->default('walk-in');
            $table->enum('status', [
                'reserved',
                'waiting',
                'serving',
                'completed',
                'skipped',
                'cancelled',
                'no-show',
            ])->default('waiting');
            $table->string('slot_key', 16)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent_hash', 32)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
            $table->index('ticket_number');
            $table->index('ip_address');
            $table->index('slot_key');
            $table->index('unique_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};