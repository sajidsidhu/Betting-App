<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->text('result_summary')->nullable()->after('parsed_data');
            $table->string('result_source_url')->nullable()->after('result_summary');
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['result_summary', 'result_source_url']);
        });
    }
};
