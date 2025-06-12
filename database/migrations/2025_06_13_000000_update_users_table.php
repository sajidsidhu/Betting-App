<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add name column and copy existing first_name + last_name
            $table->string('name')->after('id')->nullable();
            // Add profile_picture column
            $table->string('profile_picture')->nullable()->after('phone');
            
            // Drop the old columns after data migration
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->dropColumn(['name', 'profile_picture']);
        });
    }
};