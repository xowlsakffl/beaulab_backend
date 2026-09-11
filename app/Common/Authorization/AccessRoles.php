<?php

namespace App\Common\Authorization;

final class AccessRoles
{
    // =========================
    // Staff Roles (guard: staff)
    // =========================

    public const BEAULAB_SUPER_ADMIN = 'beaulab.super_admin';

    public const BEAULAB_ADMIN = 'beaulab.admin';

    public const BEAULAB_STAFF = 'beaulab.staff';

    public const BEAULAB_DEV = 'beaulab.dev';

    // =========================
    // Partner Roles
    // =========================

    // Hospital
    public const HOSPITAL_OWNER = 'hospital.owner';

    // Beauty
    public const BEAUTY_OWNER = 'beauty.owner';

    public const BEAUTY_MANAGER = 'beauty.manager';

    public const BEAUTY_STAFF = 'beauty.staff';

    // Agency
    public const AGENCY_OWNER = 'agency.owner';

    public const AGENCY_STAFF = 'agency.staff';

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
            AccessPermissions::GUARD_HOSPITAL => [
                self::HOSPITAL_OWNER,
            ],
            AccessPermissions::GUARD_BEAUTY => [
                self::BEAUTY_OWNER,
                self::BEAUTY_MANAGER,
                self::BEAUTY_STAFF,
            ],
        ];
    }

    /**
     * guard별 role => permissions 매핑
     *
     * @return array<string, array<string, list<string>>> guard => [role => permissions]
     */
    public static function mapByGuard(): array
    {
        $staffCommon = AccessPermissions::common();
        $partnerCommon = AccessPermissions::common();

        $beaulab = AccessPermissions::beaulab();
        $hospital = AccessPermissions::hospital();
        $beauty = AccessPermissions::beauty();

        // guard별로 생성될 permission 집합 (Seeder에서 그대로 생성되는 목록)
        $staffAllPermissions = AccessPermissions::byGuard()[AccessPermissions::GUARD_STAFF];

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
                    AccessPermissions::BEAULAB_HOSPITAL_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_NOTICE_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW,
                    AccessPermissions::BEAULAB_BEAUTY_SHOW,
                    AccessPermissions::BEAULAB_AGENCY_SHOW,
                    AccessPermissions::BEAULAB_USER_SHOW,
                    AccessPermissions::BEAULAB_DOCTOR_SHOW,
                    AccessPermissions::BEAULAB_EXPERT_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVALUATION_SHOW,
                    AccessPermissions::BEAULAB_TALK_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_TALK_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_VIDEO_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_SHOW,
                    AccessPermissions::BEAULAB_NOTICE_SHOW,
                    AccessPermissions::BEAULAB_FAQ_SHOW,
                ]),

                // 개발(현재는 staff 동일)
                self::BEAULAB_DEV => self::unique([
                    ...$staffCommon,
                    AccessPermissions::BEAULAB_HOSPITAL_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_NOTICE_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW,
                    AccessPermissions::BEAULAB_BEAUTY_SHOW,
                    AccessPermissions::BEAULAB_AGENCY_SHOW,
                    AccessPermissions::BEAULAB_USER_SHOW,
                    AccessPermissions::BEAULAB_DOCTOR_SHOW,
                    AccessPermissions::BEAULAB_EXPERT_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_EVALUATION_SHOW,
                    AccessPermissions::BEAULAB_TALK_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_TALK_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW,
                    AccessPermissions::BEAULAB_REPORTED_VIDEO_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_SHOW,
                    AccessPermissions::BEAULAB_NOTICE_SHOW,
                    AccessPermissions::BEAULAB_FAQ_SHOW,
                ]),
            ],

            // =========================
            // Hospital (guard: hospital)
            // =========================
            AccessPermissions::GUARD_HOSPITAL => [
                // 1병원 1계정 정책: 현재 병원 파트너는 owner 단일 role만 사용한다.
                // 다계정 정책 재도입 시 manager/staff role을 새로 정의한다.
                self::HOSPITAL_OWNER => self::unique([
                    ...$partnerCommon,
                    ...$hospital,
                ]),
            ],

            // =========================
            // Beauty (guard: beauty)
            // =========================
            AccessPermissions::GUARD_BEAUTY => [
                self::BEAUTY_OWNER => self::unique([
                    ...$partnerCommon,
                    ...$beauty,
                ]),
                self::BEAUTY_MANAGER => self::unique([
                    ...$partnerCommon,
                    AccessPermissions::BEAUTY_PROFILE_SHOW,
                    AccessPermissions::BEAUTY_PROFILE_UPDATE,
                    AccessPermissions::BEAUTY_MEMBERS_MANAGE,
                    AccessPermissions::BEAUTY_VIDEO_SHOW,
                    AccessPermissions::BEAUTY_VIDEO_CREATE,
                    AccessPermissions::BEAUTY_VIDEO_UPDATE,
                    AccessPermissions::BEAUTY_VIDEO_CANCEL,
                ]),
                self::BEAUTY_STAFF => self::unique([
                    ...$partnerCommon,
                    AccessPermissions::BEAUTY_PROFILE_SHOW,
                    AccessPermissions::BEAUTY_VIDEO_SHOW,
                    AccessPermissions::BEAUTY_VIDEO_CREATE,
                    AccessPermissions::BEAUTY_VIDEO_UPDATE,
                    AccessPermissions::BEAUTY_VIDEO_CANCEL,
                ]),
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
            foreach ($map as $role => $permissions) {
                $merged[$role] = $permissions;
            }
        }

        return $merged;
    }

    /**
     * @param  array<int, string>  $items
     * @return array<int, string>
     */
    private static function unique(array $items): array
    {
        return array_values(array_unique($items));
    }
}
