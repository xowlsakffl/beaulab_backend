<?php

namespace Database\Factories;

use App\Domains\Admin\Models\Staff;
use App\Domains\User\Models\AccountUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AccountUser>
 */
final class AccountUserFactory extends Factory
{
    protected $model = AccountUser::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'nickname'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),

            'password'          => Hash::make('password'),

            'status'            => AccountUser::STATUS_ACTIVE,

            'email_verified_at' => now(),

            'last_login_at'     => null,
        ];
    }

    public function withPassword(string $password): self
    {
        return $this->state(fn () => [
            'password' => Hash::make($password),
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn () => [
            'status' => AccountUser::STATUS_SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => [
            'status' => AccountUser::STATUS_BLOCKED,
        ]);
    }
}
