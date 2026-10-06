<?php

namespace Database\Factories;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends Factory<AccountStaff>
 */
final class AccountStaffFactory extends Factory
{
    protected $model = AccountStaff::class;

    public function definition(): array
    {
        return [
            'name' => fake('ko_KR')->name(),
            'nickname' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),

            'password' => Hash::make('password'),

            'status' => AccountStaff::STATUS_ACTIVE,

            'department' => $this->faker->randomElement(['운영팀', '고객지원팀', '사업관리팀']),
            'job_title' => $this->faker->randomElement(['매니저', '팀장', '담당자']),

            'email_verified_at' => now(),

            'last_login_at' => null,
        ];
    }

    public function withPassword(string $password): self
    {
        return $this->state(fn () => [
            'password' => Hash::make($password),
        ]);
    }

    public function withRole(string $role): self
    {
        return $this->afterCreating(function (AccountStaff $staff) use ($role): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $staff->syncRoles([$role]);
        });
    }

    public function asSuperAdmin(): self
    {
        return $this->withRole(AccessRoles::BEAULAB_SUPER_ADMIN);
    }

    public function asAdmin(): self
    {
        return $this->withRole(AccessRoles::BEAULAB_ADMIN);
    }

    public function asStaff(): self
    {
        return $this->withRole(AccessRoles::BEAULAB_STAFF);
    }

    public function asDev(): self
    {
        return $this->withRole(AccessRoles::BEAULAB_DEV);
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
