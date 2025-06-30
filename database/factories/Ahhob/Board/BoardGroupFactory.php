<?php

namespace Database\Factories\Ahhob\Board;

use App\Models\Ahhob\Board\BoardGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Board\BoardGroup>
 */
class BoardGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BoardGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * 비활성화된 게시판 그룹 상태
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}