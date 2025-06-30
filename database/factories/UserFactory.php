<?php

namespace Database\Factories;

use App\Enums\UserStatus;
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
        $username = fake()->unique()->userName();
        
        return [
            'username' => $username,
            'nickname' => fake()->unique()->name(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->optional()->phoneNumber(),
            'profile_image_path' => null,
            'status' => UserStatus::ACTIVE,
            'points' => fake()->numberBetween(0, 10000),
            'level' => fake()->numberBetween(1, 10),
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 month'),
            'last_login_ip' => fake()->optional()->ipv4(),
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
     * Set specific user attributes.
     */
    public function withAttributes(array $attributes): static
    {
        return $this->state(fn (array $defaultAttributes) => $attributes);
    }
}
