<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('bookmaker')->nullable()->after('potential_win');
            $table->timestamp('ticket_date')->nullable()->after('image_path');
            $table->boolean('is_readable')->default(true)->after('image_path');
            $table->string('error_message')->nullable()->after('is_readable');
            $table->text('errors')->nullable()->after('is_readable');
        });

        Schema::table('bets', function (Blueprint $table) {
            $table->string('team_1')->after('date_time');
            $table->string('team_2')->after('team_1');
            $table->string('bet_type')->after('team_2');
            $table->decimal('odds', 10, 2)->after('bet_type');
            $table->string('prediction')->after('odds');
            $table->string('game_type')->after('prediction');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['bookmaker', 'ticket_date', 'is_readable', 'error_message']);
        });

        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['team_1', 'team_2', 'bet_type', 'odds', 'prediction', 'game_type']);
        });
    }
};
