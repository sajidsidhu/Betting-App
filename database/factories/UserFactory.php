<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'profile_picture' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a demo user with predefined data.
     */
    public function demo(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => 'Demo',
            'last_name' => 'User',
            'email' => 'demo@example.com',
            'phone' => '+41 79 123 45 67', // Swiss phone number format
            'email_verified_at' => now(),
            'password' => Hash::make('demo123!'),
        ]);
    }

    /**
     * Create a user with a profile picture.
     */
    public function withProfilePicture(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_picture' => 'avatars/default.jpg',
        ]);
    }

    /**
     * Create a Swiss user with local formatting.
     */
    public function swiss(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => '+41 ' . fake()->numberBetween(70, 79) . ' ' . 
                      fake()->numerify('### ## ##'), // Swiss mobile format
        ]);
    }
}
