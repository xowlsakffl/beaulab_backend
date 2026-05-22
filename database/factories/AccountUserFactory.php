<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
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
            'name' => $this->faker->name(),
            'nickname' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'signup_channel' => $this->faker->randomElement(AccountUser::signupChannels()),

            'password' => Hash::make('password'),

            'status' => AccountUser::STATUS_ACTIVE,

            'email_verified_at' => now(),

            'last_login_at' => null,
            'last_accessed_at' => $this->faker->optional(0.75)->dateTimeBetween('-45 days', 'now'),
            'last_access_ip' => $this->faker->optional(0.75)->ipv4(),
        ];
    }

    public function withPassword(string $password): self
    {
        return $this->state(fn () => [
            'password' => Hash::make($password),
        ]);
    }

    public function withIdentity(string $name, string $nickname, string $email): self
    {
        return $this->state(fn () => [
            'name' => $name,
            'nickname' => $nickname,
            'email' => $email,
        ]);
    }

    public function withPhone(?string $phone): self
    {
        return $this->state(fn () => [
            'phone' => $phone,
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
