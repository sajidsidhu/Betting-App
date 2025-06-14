<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('source_language')->nullable();
            $table->string('bookmaker')->nullable();
            $table->datetime('ticket_date')->nullable();
            $table->json('translation_json')->nullable();
            $table->decimal('total_odd', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('potential_win', 10, 2)->nullable();
            $table->enum('status', ['pending', 'partial_won', 'all_won', 'lost', 'error'])->default('pending');
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_readable')->default(true);
            $table->string('error_message')->nullable();
            $table->text('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
