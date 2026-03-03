<?php

namespace Database\Factories;

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
                $this->fromSeedEnv()->make()->toArray()
            );

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $staff->syncRoles([$role]);
            $staff->syncPermissions(AccessRoles::map()[$role]);

            return $staff;
        });
    }

    /**
     * @return array{email:string,name:string,nickname:string,password:string}
     */
    private function seedPayload(): array
    {
        return [
            'email' => (string) env('SEED_STAFF_EMAIL', ''),
            'name' => (string) env('SEED_STAFF_NAME', ''),
            'nickname' => (string) env('SEED_STAFF_NICKNAME', ''),
            'password' => (string) env('SEED_STAFF_PASSWORD', ''),
        ];
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
