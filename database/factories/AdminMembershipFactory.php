<?php

namespace Database\Factories;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminMembership>
 */
final class AdminMembershipFactory extends Factory
{
    protected $model = AdminMembership::class;

    public function definition(): array
    {
        return [
            'admin_id'   => Admin::factory(), // 기본: Admin과 함께 생성
            'type'       => 'beaulab',
            'target_id'  => 0,
            'role'       => 'staff',
            'is_primary' => true,
        ];
    }

    /**
     * beaulab 내부 직원 (기본)
     */
    public function beaulabStaff(): self
    {
        return $this->state(fn () => [
            'type'       => 'beaulab',
            'target_id'  => 0,
            'role'       => 'staff',
            'is_primary' => true,
        ]);
    }

    /**
     * beaulab 최고 관리자
     */
    public function beaulabSuperAdmin(): self
    {
        return $this->state(fn () => [
            'type'       => 'beaulab',
            'target_id'  => 0,
            'role'       => 'super_admin',
            'is_primary' => true,
        ]);
    }

    /**
     * 병원 소속 관리자
     */
    public function hospital(int $hospitalId, string $role = 'owner'): self
    {
        return $this->state(fn () => [
            'type'       => 'hospital',
            'target_id'  => $hospitalId,
            'role'       => $role,
            'is_primary' => true,
        ]);
    }

    /**
     * 뷰티 소속 관리자
     */
    public function beauty(int $beautyId, string $role = 'owner'): self
    {
        return $this->state(fn () => [
            'type'       => 'beauty',
            'target_id'  => $beautyId,
            'role'       => $role,
            'is_primary' => true,
        ]);
    }

    /**
     * 에이전시 소속 관리자
     */
    public function agency(int $agencyId, string $role = 'owner'): self
    {
        return $this->state(fn () => [
            'type'       => 'agency',
            'target_id'  => $agencyId,
            'role'       => $role,
            'is_primary' => true,
        ]);
    }
}
