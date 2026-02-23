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

            $rawPassword = (string) env('SEED_STAFF_PASSWORD', '');

            $partner = AccountPartner::factory()->create([
                'email'    => "beauty{$seedKey}@owner.test",
                'nickname' => "beauty_owner_{$seedKey}",
                'name'     => "뷰티 소유주 {$seedKey}",
                'partner_type' => AccountPartner::PARTNER_BEAUTY,
                'beauty_id' => $beauty->id,
                'status'   => AccountPartner::STATUS_ACTIVE,
                'password' => $rawPassword,
            ]);

            $partner->syncRoles([
                AccessRoles::BEAUTY_OWNER,
            ]);

            $partner->syncPermissions([
                ...AccessPermissions::common(),
                ...AccessPermissions::beauty(),
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
