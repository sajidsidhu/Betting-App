<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->timestamp('date_time');
            $table->string('team_1');
            $table->string('team_2');
            $table->string('bet_type');
            $table->decimal('odds', 10, 2);
            $table->string('prediction');
            $table->string('game_type');
            $table->text('raw_text');
            $table->json('parsed_data')->nullable();
            $table->enum('status', ['pending', 'won', 'lost'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Add indexes for common queries
            $table->index(['ticket_id', 'status']);
            $table->index('game_type');
            $table->index('date_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
