<?php

namespace Database\Factories;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AccountPartner>
 */
final class AccountPartnerFactory extends Factory
{
    protected $model = AccountPartner::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'nickname'          => $this->faker->unique()->userName(),
            'email'             => $this->faker->unique()->safeEmail(),

            'password'          => Hash::make('password'),

            'status'            => AccountPartner::STATUS_ACTIVE,

            'partner_type'      => null,
            'hospital_id'       => null,
            'beauty_id'         => null,

            'email_verified_at' => now(),

            'last_login_at'     => null,
        ];
    }

    public function hospital(?Hospital $hospital = null): self
    {
        return $this->state(function () use ($hospital) {
            $hospital ??= Hospital::factory()->create();

            return [
                'partner_type' => AccountPartner::PARTNER_HOSPITAL,
                'hospital_id'  => $hospital->id,
                'beauty_id'    => null,
            ];
        });
    }

    public function beauty(?Beauty $beauty = null): self
    {
        return $this->state(function () use ($beauty) {
            $beauty ??= Beauty::factory()->create();

            return [
                'partner_type' => AccountPartner::PARTNER_BEAUTY,
                'beauty_id'    => $beauty->id,
                'hospital_id'  => null,
            ];
        });
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
            'status' => AccountPartner::STATUS_SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => [
            'status' => AccountPartner::STATUS_BLOCKED,
        ]);
    }
}
