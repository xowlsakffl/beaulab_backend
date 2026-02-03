<?php

namespace App\Modules\Admin\Authorization;

final class AdminRoles
{
    // 내부직원
    public const BEAULAB_SUPER_ADMIN = 'beaulab.super_admin';
    public const BEAULAB_ADMIN       = 'beaulab.admin';
    public const BEAULAB_STAFF       = 'beaulab.staff';
    public const BEAULAB_DEV         = 'beaulab.dev';

    // 병원
    public const HOSPITAL_OWNER      = 'hospital.owner';
    public const HOSPITAL_MANAGER    = 'hospital.manager';
    public const HOSPITAL_STAFF      = 'hospital.staff';

    // 뷰티
    public const BEAUTY_OWNER        = 'beauty.owner';
    public const BEAUTY_MANAGER      = 'beauty.manager';
    public const BEAUTY_STAFF        = 'beauty.staff';

    // 대행사
    public const AGENCY_OWNER        = 'agency.owner';
    public const AGENCY_STAFF        = 'agency.staff';

    /**
     * @return array<string, list<string>> role => permissions
     */
    public static function map(): array
    {
        $common  = AdminPermissions::common();

        $beaulab = AdminPermissions::beaulab();
        $hospital = AdminPermissions::hospital();
        $beauty   = AdminPermissions::beauty();
        $agency   = AdminPermissions::agency();

        return [
            // 뷰랩 직원
            self::BEAULAB_SUPER_ADMIN => AdminPermissions::all(),

            // 직원 전체 운영자(직원용 전체 기능)
            self::BEAULAB_ADMIN => array_values(array_unique(array_merge(
                $common,
                $beaulab
            ))),

            // 직원(조회 중심)
            self::BEAULAB_STAFF => array_values(array_unique(array_merge(
                $common,
                [
                    'beaulab.hospital.list',
                    'beaulab.hospital.show',
                    'beaulab.beauty.list',
                    'beaulab.beauty.show',
                    'beaulab.agency.list',
                    'beaulab.agency.show',
                ]
            ))),

            // 개발(현재는 staff 동일)
            self::BEAULAB_DEV => array_values(array_unique(array_merge(
                $common,
                [
                    'beaulab.hospital.list',
                    'beaulab.hospital.show',
                    'beaulab.beauty.list',
                    'beaulab.beauty.show',
                    'beaulab.agency.list',
                    'beaulab.agency.show',
                ]
            ))),

            // 병원
            self::HOSPITAL_OWNER => array_values(array_unique(array_merge(
                $common,
                $hospital
            ))),

            self::HOSPITAL_MANAGER => array_values(array_unique(array_merge(
                $common,
                $hospital
            ))),

            self::HOSPITAL_STAFF => array_values(array_unique(array_merge(
                $common,
                [
                    'hospital.profile.show',
                ]
            ))),

            // 뷰티
            self::BEAUTY_OWNER => array_values(array_unique(array_merge(
                $common,
                $beauty
            ))),

            self::BEAUTY_MANAGER => array_values(array_unique(array_merge(
                $common,
                $beauty
            ))),

            self::BEAUTY_STAFF => array_values(array_unique(array_merge(
                $common,
                [
                    'beauty.profile.show',
                ]
            ))),

            // 대행사
            self::AGENCY_OWNER => array_values(array_unique(array_merge(
                $common,
                $agency
            ))),

            self::AGENCY_STAFF => array_values(array_unique(array_merge(
                $common,
                [
                    'agency.profile.show',
                ]
            ))),
        ];
    }

    /**
     * Seeder에서 role 생성/동기화할 때 쓰는 role name 목록
     *
     * @return list<string>
     */
    public static function allRoleNames(): array
    {
        return [
            self::BEAULAB_SUPER_ADMIN,
            self::BEAULAB_ADMIN,
            self::BEAULAB_STAFF,
            self::BEAULAB_DEV,

            self::HOSPITAL_OWNER,
            self::HOSPITAL_MANAGER,
            self::HOSPITAL_STAFF,

            self::BEAUTY_OWNER,
            self::BEAUTY_MANAGER,
            self::BEAUTY_STAFF,

            self::AGENCY_OWNER,
            self::AGENCY_STAFF,
        ];
    }
}
