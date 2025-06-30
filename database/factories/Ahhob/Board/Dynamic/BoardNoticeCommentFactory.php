<?php

namespace Database\Factories\Ahhob\Board\Dynamic;

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Board\Dynamic\BoardNoticeComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Board\Dynamic\BoardNoticeComment>
 */
class BoardNoticeCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BoardNoticeComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => BoardNotice::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'password' => null,
            'is_secret' => false,
            'is_html' => false,
            'parent_id' => null,
            'depth' => 0,
            'path' => null, // 생성 후 설정됨
            'author_name' => null,
            'author_email' => null,
            'author_ip' => $this->faker->ipv4(),
            'status' => 'published',
            'admin_memo' => null,
            'like_count' => $this->faker->numberBetween(0, 50),
            'reply_count' => 0,
        ];
    }

    /**
     * Indicate that the comment is secret.
     */
    public function secret(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_secret' => true,
            'password' => bcrypt('secret'),
        ]);
    }

    /**
     * Indicate that the comment is a reply to another comment.
     */
    public function reply(BoardNoticeComment $parentComment): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $parentComment->post_id,
            'parent_id' => $parentComment->id,
            'depth' => $parentComment->depth + 1,
            'path' => $parentComment->path . '/' . ($attributes['id'] ?? '1'),
        ]);
    }

    /**
     * Indicate that the comment is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_name' => $this->faker->name(),
            'author_email' => $this->faker->email(),
        ]);
    }
}