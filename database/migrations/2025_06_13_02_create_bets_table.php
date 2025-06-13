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
            $table->text('raw_text');
            $table->json('parsed_data');
            $table->enum('status', ['pending', 'won', 'lost'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
