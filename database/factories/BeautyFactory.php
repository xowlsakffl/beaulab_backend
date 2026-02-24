<?php

namespace Database\Factories;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
final class BeautyFactory extends Factory
{
    protected $model = Beauty::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company() . ' 뷰티';

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
                Beauty::ALLOW_PENDING,
                Beauty::ALLOW_APPROVED,
                Beauty::ALLOW_REJECTED,
            ]),

            'status'          => $this->faker->randomElement([
                Beauty::STATUS_ACTIVE,
                Beauty::STATUS_SUSPENDED,
                Beauty::STATUS_WITHDRAWN,
            ]),
        ];
    }

    public function withOwner(): self
    {
        return $this->afterCreating(function (Beauty $beauty) {
            $seedKey = str_pad((string) $beauty->id, 4, '0', STR_PAD_LEFT);

            $this->createBeautyPartner(
                $beauty,
                $seedKey,
                'owner',
                '뷰티 소유주',
                AccessRoles::BEAUTY_OWNER,
                1
            );

            for ($i = 1; $i <= 3; $i++) {
                $this->createBeautyPartner(
                    $beauty,
                    $seedKey,
                    'manager',
                    '뷰티 매니저',
                    AccessRoles::BEAUTY_MANAGER,
                    $i
                );
            }

            for ($i = 1; $i <= 10; $i++) {
                $this->createBeautyPartner(
                    $beauty,
                    $seedKey,
                    'staff',
                    '뷰티 직원',
                    AccessRoles::BEAUTY_STAFF,
                    $i
                );
            }
        });
    }

    private function createBeautyPartner(
        Beauty $beauty,
        string $seedKey,
        string $type,
        string $nameLabel,
        string $role,
        int $index
    ): void {
        $rawPassword = (string) env('SEED_STAFF_PASSWORD', '');
        $suffix = $type === 'owner' ? '' : str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        $email = $type === 'owner'
            ? "beauty{$seedKey}@owner.test"
            : "beauty{$seedKey}.{$type}{$suffix}@partner.test";

        $nickname = $type === 'owner'
            ? "beauty_owner_{$seedKey}"
            : "beauty_{$type}_{$seedKey}_{$suffix}";

        $name = $type === 'owner'
            ? "{$nameLabel} {$seedKey}"
            : "{$nameLabel} {$seedKey}-{$suffix}";

        $partner = AccountPartner::factory()->create([
            'email'        => $email,
            'nickname'     => $nickname,
            'name'         => $name,
            'partner_type' => AccountPartner::PARTNER_BEAUTY,
            'beauty_id'    => $beauty->id,
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
