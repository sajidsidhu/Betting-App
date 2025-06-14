<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'phone' => '1234567890',
                'password' => bcrypt('password'),
            ]
        );

        // Case 1: Unreadable Tickets
        Ticket::factory()
            ->count(3)
            ->unreadable()
            ->create(['user_id' => $user->id]);

        // Case 2: Pending Tickets with Multiple Bets
        Ticket::factory()
            ->count(5)
            ->has(
                Bet::factory()
                    ->count(fake()->numberBetween(2, 5))
                    ->state(fn (array $attributes) => ['status' => 'pending'])
            )
            ->create(['user_id' => $user->id]);

        // Case 3: All Won Tickets
        Ticket::factory()
            ->count(3)
            ->allWon()
            ->has(
                Bet::factory()
                    ->count(fake()->numberBetween(2, 4))
                    ->won()
            )
            ->create(['user_id' => $user->id]);

        // Case 4: Lost Tickets (one lost bet is enough)
        Ticket::factory()
            ->count(3)
            ->lost()
            ->has(
                Bet::factory()
                    ->count(fake()->numberBetween(2, 4))
                    ->sequence(
                        ['status' => 'lost'],
                        ['status' => 'won'],
                        ['status' => 'won']
                    )
            )
            ->create(['user_id' => $user->id]);

        // Case 5: Partial Won Tickets
        Ticket::factory()
            ->count(3)
            ->partialWon()
            ->has(
                Bet::factory()
                    ->count(4)
                    ->sequence(
                        ['status' => 'won'],
                        ['status' => 'won'],
                        ['status' => 'pending'],
                        ['status' => 'pending']
                    )
            )
            ->create(['user_id' => $user->id]);

        // Case 6: Favorite Tickets with Mixed Status
        Ticket::factory()
            ->count(3)
            ->sequence(
                ['status' => 'pending'],
                ['status' => 'all_won'],
                ['status' => 'lost']
            )
            ->has(
                Bet::factory()
                    ->count(3)
            )
            ->create([
                'user_id' => $user->id,
                'is_favorite' => true
            ]);

        // Case 7: Old Tickets (for testing history)
        Ticket::factory()
            ->count(5)
            ->sequence(
                ['status' => 'all_won'],
                ['status' => 'lost'],
                ['status' => 'lost'],
                ['status' => 'all_won'],
                ['status' => 'partial_won']
            )
            ->has(
                Bet::factory()
                    ->count(2)
            )
            ->create([
                'user_id' => $user->id,
                'ticket_date' => fake()->dateTimeBetween('-3 months', '-1 month')
            ]);
    }
}
