<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Receptionist,
            'status' => UserStatus::Active,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::SuperAdmin]);
    }

    public function eventAdmin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::EventAdmin]);
    }

    public function receptionist(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Receptionist]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Inactive]);
    }
}
