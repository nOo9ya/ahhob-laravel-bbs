<?php

namespace Database\Factories;

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Shared\PostLike>
 */
class PostLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'likeable_type' => BoardNotice::class,
            'likeable_id' => BoardNotice::factory(),
            'user_id' => User::factory(),
            'is_like' => $this->faker->boolean(70), // 70% 확률로 좋아요
            'ip_address' => $this->faker->ipv4(),
        ];
    }

    /**
     * 좋아요 상태
     */
    public function like(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_like' => true,
        ]);
    }

    /**
     * 싫어요 상태
     */
    public function dislike(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_like' => false,
        ]);
    }
}
