<?php

namespace Database\Factories;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Partner\Models\AccountPartner;
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
            $this->createHospitalPartner(
                $hospital,
                $seedKey,
                'owner',
                '병원 소유주',
                AccessRoles::HOSPITAL_OWNER,
                1
            );

            for ($i = 1; $i <= 3; $i++) {
                $this->createHospitalPartner(
                    $hospital,
                    $seedKey,
                    'manager',
                    '병원 매니저',
                    AccessRoles::HOSPITAL_MANAGER,
                    $i
                );
            }

            for ($i = 1; $i <= 10; $i++) {
                $this->createHospitalPartner(
                    $hospital,
                    $seedKey,
                    'staff',
                    '병원 직원',
                    AccessRoles::HOSPITAL_STAFF,
                    $i
                );
            }
        });
    }

    private function createHospitalPartner(
        Hospital $hospital,
        string $seedKey,
        string $type,
        string $nameLabel,
        string $role,
        int $index
    ): void {
        $rawPassword = (string) env('SEED_STAFF_PASSWORD', '');
        $suffix = $type === 'owner' ? '' : str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        $email = $type === 'owner'
            ? "hospital{$seedKey}@owner.test"
            : "hospital{$seedKey}.{$type}{$suffix}@partner.test";

        $nickname = $type === 'owner'
            ? "hospital_owner_{$seedKey}"
            : "hospital_{$type}_{$seedKey}_{$suffix}";

        $name = $type === 'owner'
            ? "{$nameLabel} {$seedKey}"
            : "{$nameLabel} {$seedKey}-{$suffix}";

        $partner = AccountPartner::factory()->create([
            'email'        => $email,
            'nickname'     => $nickname,
            'name'         => $name,
            'partner_type' => AccountPartner::PARTNER_HOSPITAL,
            'hospital_id'  => $hospital->id,
            'status'       => AccountPartner::STATUS_ACTIVE,
            'password'     => $rawPassword,
        ]);

        $partner->syncRoles([$role]);

        $partner->syncPermissions([
            ...AccessPermissions::common(),
            ...AccessRoles::map()[$role],
        ]);
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
