<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a demo user
        User::factory()
            ->demo()
            ->withProfilePicture()
            ->create();

        // Create some Swiss users
        User::factory()
            ->count(5)
            ->swiss()
            ->create();

        // Create some regular users
        User::factory()
            ->count(5)
            ->create();

        // Create some unverified users
        User::factory()
            ->count(3)
            ->unverified()
            ->create();

        // Run the ticket seeder
        $this->call([
            TicketSeeder::class,
            PlanSeeder::class,
        ]);
    }
}
