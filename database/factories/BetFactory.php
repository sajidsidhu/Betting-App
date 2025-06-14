<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bet>
 */
class BetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gameTypes = ['soccer', 'basketball', 'hockey', 'tennis', 'volleyball'];
        $betTypes = ['1X2', 'Over/Under', 'Both Teams to Score', 'Correct Score', 'Asian Handicap'];
        
        $teamsByType = [
            'soccer' => [
                ['Manchester United', 'Liverpool'],
                ['Barcelona', 'Real Madrid'],
                ['Bayern Munich', 'Borussia Dortmund'],
                ['PSG', 'Marseille'],
                ['Juventus', 'AC Milan']
            ],
            'basketball' => [
                ['LA Lakers', 'Boston Celtics'],
                ['Miami Heat', 'Chicago Bulls'],
                ['Golden State Warriors', 'Phoenix Suns']
            ],
            'hockey' => [
                ['Montreal Canadiens', 'Toronto Maple Leafs'],
                ['New York Rangers', 'Boston Bruins']
            ],
            'tennis' => [
                ['Roger Federer', 'Rafael Nadal'],
                ['Novak Djokovic', 'Andy Murray']
            ],
            'volleyball' => [
                ['Brazil', 'Poland'],
                ['Italy', 'France']
            ]
        ];

        $gameType = fake()->randomElement($gameTypes);
        $teams = fake()->randomElement($teamsByType[$gameType]);

        $rawText = sprintf(
            "%s vs %s - %s",
            $teams[0],
            $teams[1],
            fake()->randomElement($betTypes)
        );

        return [
            'ticket_id' => Ticket::factory(),
            'date_time' => fake()->dateTimeBetween('now', '+1 month'),
            'team_1' => $teams[0],
            'team_2' => $teams[1],
            'bet_type' => fake()->randomElement($betTypes),
            'odds' => fake()->randomFloat(2, 1.1, 10.0),
            'prediction' => fake()->randomElement(['1', 'X', '2', 'Yes', 'No', 'Over 2.5', 'Under 2.5']),
            'game_type' => $gameType,
            'raw_text' => $rawText,
            'status' => 'pending',
            'parsed_data' => null,
            'verified_at' => null
        ];
    }

    /**
     * Indicate that the bet is won.
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'verified_at' => fake()->dateTimeBetween('-1 day', 'now')
        ]);
    }

    /**
     * Indicate that the bet is lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'verified_at' => fake()->dateTimeBetween('-1 day', 'now')
        ]);
    }
}
