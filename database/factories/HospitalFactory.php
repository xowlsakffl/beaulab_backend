<?php

namespace Database\Factories;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminMembership;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
final class HospitalFactory extends Factory
{
    protected $model = Hospital::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company() . ' 병원';

        return [
            'name'            => $name,
            'description'     => $this->faker->optional(0.7)->paragraph(),
            'address'         => $this->faker->optional(0.9)->address(),
            'address_detail'  => $this->faker->optional(0.5)->secondaryAddress(),

            'latitude'        => $this->faker->optional(0.8)->latitude(33.0, 38.6),
            'longitude'       => $this->faker->optional(0.8)->longitude(124.5, 132.0),

            'tel'             => $this->faker->optional(0.9)->phoneNumber(),
            'email'           => $this->faker->optional(0.7)->companyEmail(),

            'consulting_hours'=> $this->faker->optional(0.6)->sentence(10),
            'direction'       => $this->faker->optional(0.6)->sentence(12),

            'view_count'      => $this->faker->numberBetween(0, 50000),

            'allow_status'    => $this->faker->randomElement([
                Hospital::ALLOW_PENDING,
                Hospital::ALLOW_APPROVED,
                Hospital::ALLOW_REJECTED,
            ]),

            'status'          => $this->faker->randomElement([
                Hospital::STATUS_ACTIVE,
                Hospital::STATUS_SUSPENDED,
                Hospital::STATUS_WITHDRAWN,
            ]),
        ];
    }

    public function withOwner(): self
    {
        return $this->afterCreating(function (Hospital $hospital) {

            $seedKey = str_pad((string) $hospital->id, 4, '0', STR_PAD_LEFT);

            $rawPassword = (string) env('SEED_ADMIN_PASSWORD', '');

            $admin = Admin::factory()->create([
                'email'    => "hospital{$seedKey}@owner.test",
                'nickname' => "hospital_owner_{$seedKey}",
                'name'     => "병원 소유주 {$seedKey}",
                'status'   => Admin::STATUS_ACTIVE,
                'password' => $rawPassword,
            ]);

            $membership = AdminMembership::factory()
                ->hospital($hospital->id, role: 'hospital.owner')
                ->create([
                    'admin_id' => $admin->id,
                ]);

            $admin->forceFill([
                'active_membership_id' => $membership->id,
            ])->save();

            $admin->syncRoles([
                'hospital.owner',
            ]);
        });
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'allow_status' => Hospital::ALLOW_APPROVED,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => Hospital::STATUS_ACTIVE,
        ]);
    }
}
