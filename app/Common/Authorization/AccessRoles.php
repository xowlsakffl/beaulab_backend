<?php

namespace App\Common\Authorization;

final class AccessRoles
{
    // =========================
    // Staff Roles (guard: staff)
    // =========================

    public const BEAULAB_SUPER_ADMIN = 'beaulab.super_admin';
    public const BEAULAB_ADMIN       = 'beaulab.admin';
    public const BEAULAB_STAFF       = 'beaulab.staff';
    public const BEAULAB_DEV         = 'beaulab.dev';

    // =========================
    // Partner Roles (guard: partner)
    // =========================

    // Hospital
    public const HOSPITAL_OWNER      = 'hospital.owner';
    public const HOSPITAL_MANAGER    = 'hospital.manager';
    public const HOSPITAL_STAFF      = 'hospital.staff';

    // Beauty
    public const BEAUTY_OWNER        = 'beauty.owner';
    public const BEAUTY_MANAGER      = 'beauty.manager';
    public const BEAUTY_STAFF        = 'beauty.staff';

    // Agency
    public const AGENCY_OWNER        = 'agency.owner';
    public const AGENCY_STAFF        = 'agency.staff';

    /**
     * guard별 role 목록
     *
     * @return array<string, list<string>>
     */
    public static function roleNamesByGuard(): array
    {
        return [
            AccessPermissions::GUARD_STAFF => [
                self::BEAULAB_SUPER_ADMIN,
                self::BEAULAB_ADMIN,
                self::BEAULAB_STAFF,
                self::BEAULAB_DEV,
            ],
            AccessPermissions::GUARD_PARTNER => [
                self::HOSPITAL_OWNER,
                self::HOSPITAL_MANAGER,
                self::HOSPITAL_STAFF,

                self::BEAUTY_OWNER,
                self::BEAUTY_MANAGER,
                self::BEAUTY_STAFF,

                self::AGENCY_OWNER,
                self::AGENCY_STAFF,
            ],
            AccessPermissions::GUARD_USER => [
                // 유저는 role을 안 쓰면 비워둬도 됨.
                // 필요하면 예: 'user.basic'
            ],
        ];
    }

    /**
     * guard별 role => permissions 매핑
     *
     * @return array<string, array<string, list<string>>>  guard => [role => permissions]
     */
    public static function mapByGuard(): array
    {
        $staffCommon   = AccessPermissions::common();
        $partnerCommon = AccessPermissions::common();

        $beaulab  = AccessPermissions::beaulab();
        $hospital = AccessPermissions::hospital();
        $beauty   = AccessPermissions::beauty();
        $agency   = AccessPermissions::agency();

        // guard별로 생성될 permission 집합 (Seeder에서 그대로 생성되는 목록)
        $staffAllPermissions   = AccessPermissions::byGuard()[AccessPermissions::GUARD_STAFF];
        $partnerAllPermissions = AccessPermissions::byGuard()[AccessPermissions::GUARD_PARTNER];
        $userAllPermissions    = AccessPermissions::byGuard()[AccessPermissions::GUARD_USER];

        return [
            // =========================
            // Staff (guard: staff)
            // =========================
            AccessPermissions::GUARD_STAFF => [
                // staff guard에 존재하는 permission 전부
                self::BEAULAB_SUPER_ADMIN => $staffAllPermissions,

                self::BEAULAB_ADMIN => self::unique([
                    ...$staffCommon,
                    ...$beaulab,
                ]),

                self::BEAULAB_STAFF => self::unique([
                    ...$staffCommon,
                    // 조회 중심
                    'beaulab.hospital.show',
                    'beaulab.beauty.show',
                    'beaulab.agency.show',
                    'beaulab.user.show',
                ]),

                // 개발(현재는 staff 동일)
                self::BEAULAB_DEV => self::unique([
                    ...$staffCommon,
                    'beaulab.hospital.show',
                    'beaulab.beauty.show',
                    'beaulab.agency.show',
                    'beaulab.user.show',
                ]),
            ],

            // =========================
            // Partner (guard: partner)
            // =========================
            AccessPermissions::GUARD_PARTNER => [
                self::HOSPITAL_OWNER => self::unique([
                    ...$partnerCommon,
                    ...$hospital,
                ]),
                self::HOSPITAL_MANAGER => self::unique([
                    ...$partnerCommon,
                    'hospital.profile.show',
                    'hospital.profile.update',
                    'hospital.members.manage',
                ]),
                self::HOSPITAL_STAFF => self::unique([
                    ...$partnerCommon,
                    'hospital.profile.show',
                ]),

                self::BEAUTY_OWNER => self::unique([
                    ...$partnerCommon,
                    ...$beauty,
                ]),
                self::BEAUTY_MANAGER => self::unique([
                    ...$partnerCommon,
                    'beauty.profile.show',
                    'beauty.profile.update',
                    'beauty.members.manage',
                ]),
                self::BEAUTY_STAFF => self::unique([
                    ...$partnerCommon,
                    'beauty.profile.show',
                ]),

                self::AGENCY_OWNER => self::unique([
                    ...$partnerCommon,
                    ...$agency,
                ]),
                self::AGENCY_STAFF => self::unique([
                    ...$partnerCommon,
                    'agency.profile.show',
                ]),
            ],

            // =========================
            // User (guard: user)
            // =========================
            AccessPermissions::GUARD_USER => [
                // 유저는 role 기반을 안 쓰면 비워둬도 됨.
                // role을 쓴다면 예: 'user.basic' => $userAllPermissions
            ],
        ];
    }

    /**
     * 기존 인터페이스 호환용:
     * role => permissions (staff + partner만 합친 형태)
     *
     * @return array<string, list<string>>
     */
    public static function map(): array
    {
        $merged = [];

        foreach (self::mapByGuard() as $guard => $map) {
            // user는 role 안 쓰는 전제면 비어있음
            foreach ($map as $role => $permissions) {
                $merged[$role] = $permissions;
            }
        }

        return $merged;
    }

    /**
     * @param array<int, string> $items
     * @return array<int, string>
     */
    private static function unique(array $items): array
    {
        return array_values(array_unique($items));
    }
}
