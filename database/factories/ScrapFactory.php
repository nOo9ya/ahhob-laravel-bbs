<?php

namespace Database\Factories;

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Shared\Scrap>
 */
class ScrapFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scrapable_type' => BoardNotice::class,
            'scrapable_id' => BoardNotice::factory(),
            'user_id' => User::factory(),
            'memo' => $this->faker->optional(0.7)->sentence(),
            'category' => $this->faker->randomElement(['default', 'favorite', 'important', 'later']),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'ip_address' => $this->faker->ipv4(),
        ];
    }

    /**
     * 기본 카테고리
     */
    public function defaultCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'default',
        ]);
    }

    /**
     * 즐겨찾기 카테고리
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'favorite',
            'memo' => '즐겨찾기',
        ]);
    }

    /**
     * 중요 카테고리
     */
    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'important',
            'memo' => '중요한 내용',
        ]);
    }

    /**
     * 나중에 읽기 카테고리
     */
    public function readLater(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'later',
            'memo' => '나중에 읽기',
        ]);
    }
}
