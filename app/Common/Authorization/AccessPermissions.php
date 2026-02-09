<?php

namespace App\Common\Authorization;

final class AccessPermissions
{
    public const GUARD_STAFF   = 'staff';
    public const GUARD_PARTNER = 'partner';
    public const GUARD_USER    = 'user';

    /**
     * guard별 생성해야 할 permission 목록 (Seeder는 이걸 기준으로 생성)
     *
     * - staff: 내부 직원(뷰랩)
     * - partner: 병원/뷰티/대행사 파트너
     * - user: 일반 사용자(앱) => 프로필만
     */
    public static function byGuard(): array
    {
        return [
            self::GUARD_STAFF => self::unique([
                ...self::common(),   // staff/partner 공통(관리영역)
                ...self::beaulab(),  // staff 전용
            ]),

            self::GUARD_PARTNER => self::unique([
                ...self::common(),   // staff/partner 공통(관리영역)
                ...self::hospital(),
                ...self::beauty(),
                ...self::agency(),
            ]),

            self::GUARD_USER => self::unique([
                ...self::user(),
            ]),
        ];
    }

    /**
     * 전체 permission 이름 목록(참고/검증용)
     * guard별 정의를 합쳐서 자동으로 만든다.
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::byGuard() as $permissions) {
            $all = array_merge($all, $permissions);
        }

        return self::unique($all);
    }

    /**
     * Staff/Partner 공통(관리 영역 공통) 권한
     * - User(앱)는 여기에 포함하지 않는다.
     */
    public static function common(): array
    {
        return [
            // 관리영역 접근
            'common.access',

            // 기본 화면/계정
            'common.dashboard.show',
            'common.profile.show',
            'common.profile.update',
        ];
    }

    /**
     * Staff(뷰랩) 전용 권한
     */
    public static function beaulab(): array
    {
        return [
            // 병원
            'beaulab.hospital.list',
            'beaulab.hospital.show',
            'beaulab.hospital.create',
            'beaulab.hospital.update',

            // 뷰티
            'beaulab.beauty.list',
            'beaulab.beauty.show',
            'beaulab.beauty.create',
            'beaulab.beauty.update',

            // 대행사
            'beaulab.agency.list',
            'beaulab.agency.show',
            'beaulab.agency.create',
            'beaulab.agency.update',
        ];
    }

    /**
     * Partner - Hospital 권한
     */
    public static function hospital(): array
    {
        return [
            'hospital.profile.show',
            'hospital.profile.update',
            'hospital.members.manage',
        ];
    }

    /**
     * Partner - Beauty 권한
     */
    public static function beauty(): array
    {
        return [
            'beauty.profile.show',
            'beauty.profile.update',
            'beauty.members.manage',
        ];
    }

    /**
     * Partner - Agency 권한
     */
    public static function agency(): array
    {
        return [
            'agency.profile.show',
            'agency.profile.update',
            'agency.members.manage',
        ];
    }

    /**
     * User(앱) 권한
     */
    public static function user(): array
    {
        return [
            'user.profile.show',
            'user.profile.update',
        ];
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    private static function unique(array $permissions): array
    {
        return array_values(array_unique($permissions));
    }
}
