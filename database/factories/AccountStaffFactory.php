<?php

namespace Database\Factories;

use App\Domains\Admin\Models\Staff;
use App\Domains\Staff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AccountStaff>
 */
final class AccountStaffFactory extends Factory
{
    protected $model = AccountStaff::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'nickname'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),

            'password'          => Hash::make('password'),

            'status'            => AccountStaff::STATUS_ACTIVE,

            'department'        => $this->faker->jobTitle(),
            'job_title'          => $this->faker->jobTitle(),

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
            'status' => AccountStaff::STATUS_SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => [
            'status' => AccountStaff::STATUS_BLOCKED,
        ]);
    }
}
