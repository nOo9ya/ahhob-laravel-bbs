<?php

namespace Database\Factories\Ahhob\Board\Dynamic;

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Board\Dynamic\BoardNotice>
 */
class BoardNoticeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BoardNotice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'password' => null,
            'is_notice' => false,
            'is_secret' => false,
            'is_html' => false,
            'author_name' => null,
            'author_email' => null,
            'author_ip' => $this->faker->ipv4(),
            'slug' => null,
            'excerpt' => null,
            'meta_data' => null,
            'status' => 'published',
            'published_at' => now(),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'like_count' => $this->faker->numberBetween(0, 100),
            'comment_count' => 0,
            'attachment_count' => 0,
        ];
    }

    /**
     * Indicate that the post is a notice.
     */
    public function notice(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_notice' => true,
        ]);
    }

    /**
     * Indicate that the post is secret.
     */
    public function secret(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_secret' => true,
            'password' => bcrypt('secret'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}