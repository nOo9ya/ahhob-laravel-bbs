<?php

namespace Database\Factories;

use App\Models\Ahhob\Admin\Admin;
use App\Enums\AdminStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ahhob\Admin\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'), // 기본 테스트 비밀번호
            'role' => $this->faker->randomElement(['admin', 'manager']),
            'status' => AdminStatus::ACTIVE,
            'permissions' => $this->faker->randomElements([
                'user.read', 'user.write', 'user.delete',
                'post.read', 'post.write', 'post.delete',
                'board.read', 'board.write', 'board.delete',
                'setting.read', 'setting.write',
            ], $this->faker->numberBetween(1, 5)),
            'last_login_at' => null,
            'last_login_ip' => null,
            'remember_token' => null,
        ];
    }

    /**
     * Indicate that the admin is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
            'permissions' => [], // 슈퍼 관리자는 모든 권한을 가지므로 빈 배열
        ]);
    }

    /**
     * Indicate that the admin is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdminStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdminStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the admin has specific permissions.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Indicate that the admin has a specific role.
     */
    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }
}
