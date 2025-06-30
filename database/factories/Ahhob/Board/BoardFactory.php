<?php

namespace Database\Factories\Ahhob\Board;

use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\BoardGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Board\Board>
 */
class BoardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Board::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_group_id' => BoardGroup::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(3),
            'description' => $this->faker->sentence(),
            'list_template' => 'default',
            'view_template' => 'default',
            'write_template' => 'default',
            'read_permission' => 'all',
            'write_permission' => 'member',
            'comment_permission' => 'member',
            'use_comment' => true,
            'use_attachment' => true,
            'use_editor' => true,
            'use_like' => true,
            'use_secret' => false,
            'use_notice' => true,
            'posts_per_page' => 20,
            'max_attachment_size' => 10240,
            'max_attachment_count' => 5,
            'write_point' => 0,
            'comment_point' => 0,
            'read_point' => 0,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'post_count' => 0,
        ];
    }

    /**
     * 비활성화된 게시판 상태
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 특정 권한 설정
     */
    public function withPermissions(string $read = 'all', string $write = 'member', string $comment = 'member'): static
    {
        return $this->state(fn (array $attributes) => [
            'read_permission' => $read,
            'write_permission' => $write,
            'comment_permission' => $comment,
        ]);
    }

    /**
     * 관리자 전용 권한 설정
     */
    public function adminOnly(): static
    {
        return $this->withPermissions('admin', 'admin', 'admin');
    }

    /**
     * 회원 전용 권한 설정
     */
    public function memberOnly(): static
    {
        return $this->withPermissions('member', 'member', 'member');
    }
}