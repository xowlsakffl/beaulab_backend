<?php

namespace Database\Factories;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
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
            'name' => $this->faker->name(),
            'nickname' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),

            'password' => Hash::make('password'),

            'status' => AccountStaff::STATUS_ACTIVE,

            'department' => $this->faker->jobTitle(),
            'job_title' => $this->faker->jobTitle(),

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

    public function fromSeedEnv(): self
    {
        $payload = $this->seedPayload();

        return $this
            ->withPassword($payload['password'])
            ->state(fn () => [
                'email' => $payload['email'],
                'name' => $payload['name'],
                'nickname' => $payload['nickname'],
                'department' => '',
                'job_title' => '',
                'status' => AccountStaff::STATUS_ACTIVE,
            ]);
    }

    public function hasSeedCredentials(): bool
    {
        $payload = $this->seedPayload();

        return $payload['email'] !== '' && $payload['password'] !== '';
    }

    public function createSeededSuperAdmin(): AccountStaff
    {
        $payload = $this->seedPayload();
        $role = AccessRoles::BEAULAB_SUPER_ADMIN;

        return DB::transaction(function () use ($payload, $role): AccountStaff {
            $staff = AccountStaff::query()->firstOrCreate(
                ['email' => $payload['email']],
                $this->fromSeedEnv()->raw()
            );

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $staff->syncRoles([$role]);
            $staff->syncPermissions(AccessRoles::map()[$role]);

            return $staff;
        });
    }

    /**
     * @return array<string, AccountStaff>
     */
    public function createSeededRoleTestAccounts(): array
    {
        $payload = $this->seedPayload();
        $definitions = [
            AccessRoles::BEAULAB_SUPER_ADMIN => ['nickname' => 'test_super_admin', 'name' => '테스트 최고관리자', 'department' => '개발팀'],
            AccessRoles::BEAULAB_ADMIN => ['nickname' => 'test_admin', 'name' => '테스트 관리자', 'department' => '운영팀'],
            AccessRoles::BEAULAB_STAFF => ['nickname' => 'test_staff', 'name' => '테스트 직원', 'department' => '운영팀'],
            AccessRoles::BEAULAB_DEV => ['nickname' => 'test_dev', 'name' => '테스트 개발자', 'department' => '개발팀'],
        ];

        return DB::transaction(function () use ($definitions, $payload): array {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $accounts = [];

            foreach ($definitions as $role => $definition) {
                $staff = AccountStaff::query()->updateOrCreate(
                    ['nickname' => $definition['nickname']],
                    [
                        'name' => $definition['name'],
                        'email' => $definition['nickname'].'@beaulab.local',
                        'email_verified_at' => now(),
                        'password' => Hash::make($payload['password']),
                        'department' => $definition['department'],
                        'job_title' => $definition['name'],
                        'status' => AccountStaff::STATUS_ACTIVE,
                    ],
                );

                $staff->syncRoles([$role]);
                $staff->syncPermissions(
                    $role === AccessRoles::BEAULAB_STAFF ? $this->staffViewPermissions() : [],
                );
                $accounts[$role] = $staff;
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $accounts;
        });
    }

    /**
     * @return array{email:string,name:string,nickname:string,password:string}
     */
    private function seedPayload(): array
    {
        $seedConfig = config('seeding.staff', []);

        return [
            'email' => trim((string) ($seedConfig['email'] ?? '')),
            'name' => trim((string) ($seedConfig['name'] ?? '관리자')),
            'nickname' => trim((string) ($seedConfig['nickname'] ?? 'admin')),
            'password' => (string) ($seedConfig['password'] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    private function staffViewPermissions(): array
    {
        return array_values(array_filter(
            AccessPermissions::byGuard()[AccessPermissions::GUARD_STAFF],
            static fn (string $permission): bool => str_ends_with($permission, '.show'),
        ));
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
