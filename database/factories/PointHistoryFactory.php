<?php

namespace Database\Factories;

use App\Models\Ahhob\Board\Dynamic\BoardNotice;
use App\Models\Ahhob\Shared\PointHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Shared\PointHistory>
 */
class PointHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['earned', 'spent']);
        $amount = $type === 'earned' 
            ? $this->faker->numberBetween(1, 100)
            : -$this->faker->numberBetween(1, 50);

        return [
            'user_id' => User::factory(),
            'amount' => $amount,
            'type' => $type,
            'reason' => $this->faker->randomElement([
                'post_create',
                'comment_create', 
                'like_received',
                'daily_attendance',
                'bonus',
            ]),
            'description' => $this->faker->optional(0.7)->sentence(),
            'related_type' => $this->faker->optional(0.5)->randomElement([
                BoardNotice::class,
            ]),
            'related_id' => function (array $attributes) {
                return $attributes['related_type'] ? BoardNotice::factory() : null;
            },
            'giver_id' => $this->faker->optional(0.3)->randomElement([
                User::factory(),
                null,
            ]),
            'expires_at' => $type === 'earned' 
                ? $this->faker->optional(0.8)->dateTimeBetween('now', '+1 year')
                : null,
            'ip_address' => $this->faker->ipv4(),
        ];
    }

    /**
     * 포인트 획득 상태
     */
    public function earned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => $this->faker->numberBetween(1, 100),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * 포인트 사용 상태
     */
    public function spent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spent',
            'amount' => -$this->faker->numberBetween(1, 50),
            'expires_at' => null,
        ]);
    }

    /**
     * 포인트 만료 상태
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expired',
            'amount' => -$this->faker->numberBetween(1, 50),
            'reason' => 'point_expiry',
            'expired_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * 게시글 작성 포인트
     */
    public function postCreate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => 10,
            'reason' => 'post_create',
            'description' => '게시글 작성',
            'related_type' => BoardNotice::class,
            'related_id' => BoardNotice::factory(),
        ]);
    }

    /**
     * 댓글 작성 포인트
     */
    public function commentCreate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => 5,
            'reason' => 'comment_create',
            'description' => '댓글 작성',
            'related_type' => BoardNotice::class,
            'related_id' => BoardNotice::factory(),
        ]);
    }

    /**
     * 좋아요 받기 포인트
     */
    public function likeReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => 2,
            'reason' => 'like_received',
            'description' => '좋아요 받음',
            'related_type' => BoardNotice::class,
            'related_id' => BoardNotice::factory(),
            'giver_id' => User::factory(),
        ]);
    }

    /**
     * 출석 체크 포인트
     */
    public function dailyAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => 10,
            'reason' => 'daily_attendance',
            'description' => '출석 체크',
        ]);
    }

    /**
     * 포인트 전송
     */
    public function transferSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spent',
            'amount' => -$this->faker->numberBetween(10, 100),
            'reason' => 'transfer_sent',
            'description' => '포인트 전송',
        ]);
    }

    /**
     * 포인트 받기
     */
    public function transferReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => $this->faker->numberBetween(10, 100),
            'reason' => 'transfer_received',
            'description' => '포인트 받음',
            'giver_id' => User::factory(),
        ]);
    }

    /**
     * 관리자 조정
     */
    public function adminAdjustment(): static
    {
        $amount = $this->faker->numberBetween(-100, 100);
        
        return $this->state(fn (array $attributes) => [
            'type' => $amount > 0 ? 'earned' : 'spent',
            'amount' => $amount,
            'reason' => 'admin_adjustment',
            'description' => '관리자 포인트 조정',
            'admin_id' => User::factory(),
        ]);
    }

    /**
     * 만료 임박 포인트
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => $this->faker->numberBetween(1, 100),
            'expires_at' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }
}