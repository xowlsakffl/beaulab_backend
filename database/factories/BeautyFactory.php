<?php

namespace Database\Factories;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beauty>
 */
final class BeautyFactory extends Factory
{
    private const MANAGER_SEED_COUNT = 1;
    private const STAFF_SEED_COUNT = 2;

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

    public function withAccountBeauty(): self
    {
        return $this->afterCreating(function (Beauty $beauty) {
            $seedKey = str_pad((string) $beauty->id, 4, '0', STR_PAD_LEFT);

            $this->createAccountBeauty(
                $beauty,
                $seedKey,
                'owner',
                '뷰티 소유주',
                AccessRoles::BEAUTY_OWNER,
                1
            );

            for ($i = 1; $i <= self::MANAGER_SEED_COUNT; $i++) {
                $this->createAccountBeauty(
                    $beauty,
                    $seedKey,
                    'manager',
                    '뷰티 매니저',
                    AccessRoles::BEAUTY_MANAGER,
                    $i
                );
            }

            for ($i = 1; $i <= self::STAFF_SEED_COUNT; $i++) {
                $this->createAccountBeauty(
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

    public function withBusinessRegistration(): self
    {
        return $this->afterCreating(function (Beauty $beauty): void {
            BeautyBusinessRegistration::query()->create([
                'beauty_id' => $beauty->id,
                'business_number' => $this->faker->unique()->numerify('###-##-#####'),
                'company_name' => $beauty->name,
                'ceo_name' => $this->faker->name(),
                'business_type' => $this->faker->randomElement(['미용업', '서비스업']),
                'business_item' => $this->faker->randomElement(['헤어', '피부관리', '네일']),
                'business_address' => $beauty->address,
                'business_address_detail' => $beauty->address_detail,
                'issued_at' => $this->faker->date(),
                'status' => BeautyBusinessRegistration::STATUS_ACTIVE,
            ]);
        });
    }

    public function withSeedMedia(int $galleryCount = 3): self
    {
        return $this->afterCreating(function (Beauty $beauty) use ($galleryCount): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $beauty,
                SeedMediaFactory::image("beauty-logo-{$beauty->id}"),
                'logo',
                'beauty',
                'logo',
            );

            $mediaAttachAction->attachMany(
                $beauty,
                SeedMediaFactory::images("beauty-gallery-{$beauty->id}", max(1, $galleryCount)),
                'gallery',
                'beauty',
                'gallery',
                true,
            );

            $businessRegistration = $beauty->businessRegistration()->first();

            if ($businessRegistration instanceof BeautyBusinessRegistration) {
                $mediaAttachAction->attachOne(
                    $businessRegistration,
                    SeedMediaFactory::image("beauty-business-registration-{$beauty->id}"),
                    'business_registration_file',
                    'beauty',
                    'business-registration',
                );
            }
        });
    }

    private function createAccountBeauty(
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
            : "beauty{$seedKey}.{$type}{$suffix}@beauty.test";

        $nickname = $type === 'owner'
            ? "beauty_owner_{$seedKey}"
            : "beauty_{$type}_{$seedKey}_{$suffix}";

        $name = $type === 'owner'
            ? "{$nameLabel} {$seedKey}"
            : "{$nameLabel} {$seedKey}-{$suffix}";

        $accountBeautyFactory = AccountBeauty::factory()
            ->forBeauty($beauty)
            ->withIdentity($name, $nickname, $email)
            ->active();

        if ($rawPassword !== '') {
            $accountBeautyFactory = $accountBeautyFactory->withPassword($rawPassword);
        }

        $accountBeauty = $accountBeautyFactory->create();

        $accountBeauty->syncRoles([$role]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'allow_status' => Beauty::ALLOW_APPROVED,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => Beauty::STATUS_ACTIVE,
        ]);
    }
}
