<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookmakers = ['Swisslos', 'Sporttip', 'Bet365', 'Bwin', 'William Hill'];
        
        return [
            'user_id' => User::factory(),
            'image_path' => 'tickets/sample.jpg',
            'source_language' => fake()->randomElement(['en', 'de', 'fr', 'it']),
            'bookmaker' => fake()->randomElement($bookmakers),
            'ticket_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_odd' => fake()->randomFloat(2, 1.5, 100),
            'total_price' => fake()->randomFloat(2, 1, 1000),
            'potential_win' => function (array $attributes) {
                return round($attributes['total_odd'] * $attributes['total_price'], 2);
            },
            'status' => 'pending',
            'is_favorite' => fake()->boolean(20),
            'is_readable' => true,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the ticket is unreadable.
     */
    public function unreadable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_readable' => false,
            'error_message' => fake()->randomElement([
                'Image is too blurry',
                'Poor image quality',
                'Text not legible',
                'Image too dark',
                'Required information missing'
            ]),
            'status' => 'error'
        ]);
    }

    /**
     * Indicate that all bets are won.
     */
    public function allWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'all_won'
        ]);
    }

    /**
     * Indicate that some bets are won and some are pending.
     */
    public function partialWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial_won'
        ]);
    }

    /**
     * Indicate that the ticket is lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost'
        ]);
    }
}
