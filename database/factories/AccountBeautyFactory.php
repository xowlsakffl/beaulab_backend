<?php

namespace Database\Factories;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AccountBeauty>
 */
final class AccountBeautyFactory extends Factory
{
    protected $model = AccountBeauty::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'nickname'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),

            'password'          => Hash::make('password'),

            'status'            => AccountBeauty::STATUS_ACTIVE,

            'beauty_id'         => null,

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
            'status' => AccountBeauty::STATUS_SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => [
            'status' => AccountBeauty::STATUS_BLOCKED,
        ]);
    }
}
