<?php

namespace Database\Factories;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AccountHospital>
 */
final class AccountHospitalFactory extends Factory
{
    protected $model = AccountHospital::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'nickname'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),

            'password'          => Hash::make('password'),

            'status'            => AccountHospital::STATUS_ACTIVE,

            'hospital_id'         => null,

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

    public function forHospital(Hospital|int $hospital): self
    {
        return $this->state(fn () => [
            'hospital_id' => $hospital instanceof Hospital ? $hospital->getKey() : $hospital,
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

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => AccountHospital::STATUS_ACTIVE,
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn () => [
            'status' => AccountHospital::STATUS_SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => [
            'status' => AccountHospital::STATUS_BLOCKED,
        ]);
    }
}
