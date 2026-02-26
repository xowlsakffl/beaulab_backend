<?php

namespace App\Common\Authorization;

final class AccessPermissions
{
    public const GUARD_STAFF   = 'staff';
    public const GUARD_PARTNER = 'partner';
    public const GUARD_USER    = 'user';

    // Common
    public const COMMON_ACCESS = 'common.access';
    public const COMMON_DASHBOARD_SHOW = 'common.dashboard.show';
    public const COMMON_PROFILE_SHOW = 'common.profile.show';
    public const COMMON_PROFILE_UPDATE = 'common.profile.update';

    // Beaulab
    public const BEAULAB_HOSPITAL_SHOW = 'beaulab.hospital.show';
    public const BEAULAB_HOSPITAL_CREATE = 'beaulab.hospital.create';
    public const BEAULAB_HOSPITAL_UPDATE = 'beaulab.hospital.update';
    public const BEAULAB_HOSPITAL_DELETE = 'beaulab.hospital.delete';
    public const BEAULAB_BEAUTY_SHOW = 'beaulab.beauty.show';
    public const BEAULAB_BEAUTY_CREATE = 'beaulab.beauty.create';
    public const BEAULAB_BEAUTY_UPDATE = 'beaulab.beauty.update';
    public const BEAULAB_BEAUTY_DELETE = 'beaulab.beauty.delete';
    public const BEAULAB_AGENCY_SHOW = 'beaulab.agency.show';
    public const BEAULAB_AGENCY_CREATE = 'beaulab.agency.create';
    public const BEAULAB_AGENCY_UPDATE = 'beaulab.agency.update';
    public const BEAULAB_AGENCY_DELETE = 'beaulab.agency.delete';
    public const BEAULAB_USER_SHOW = 'beaulab.user.show';
    public const BEAULAB_USER_UPDATE = 'beaulab.user.update';
    public const BEAULAB_USER_DELETE = 'beaulab.user.delete';
    public const BEAULAB_DOCTOR_SHOW = 'beaulab.doctor.show';
    public const BEAULAB_DOCTOR_CREATE = 'beaulab.doctor.create';
    public const BEAULAB_DOCTOR_UPDATE = 'beaulab.doctor.update';
    public const BEAULAB_DOCTOR_DELETE = 'beaulab.doctor.delete';
    public const BEAULAB_EXPERT_SHOW = 'beaulab.expert.show';
    public const BEAULAB_EXPERT_CREATE = 'beaulab.expert.create';
    public const BEAULAB_EXPERT_UPDATE = 'beaulab.expert.update';
    public const BEAULAB_EXPERT_DELETE = 'beaulab.expert.delete';

    public const BEAULAB_VIDEO_REQUEST_SHOW = 'beaulab.video-request.show';
    public const BEAULAB_VIDEO_REQUEST_UPDATE = 'beaulab.video-request.update';
    public const BEAULAB_VIDEO_REQUEST_DELETE = 'beaulab.video-request.delete';

    // Hospital
    public const HOSPITAL_PROFILE_SHOW = 'hospital.profile.show';
    public const HOSPITAL_PROFILE_UPDATE = 'hospital.profile.update';
    public const HOSPITAL_PROFILE_DELETE = 'hospital.profile.delete';
    public const HOSPITAL_MEMBERS_MANAGE = 'hospital.members.manage';

    public const HOSPITAL_VIDEO_REQUEST_SHOW = 'hospital.video-request.show';
    public const HOSPITAL_VIDEO_REQUEST_CREATE = 'hospital.video-request.create';
    public const HOSPITAL_VIDEO_REQUEST_UPDATE = 'hospital.video-request.update';
    public const HOSPITAL_VIDEO_REQUEST_CANCEL = 'hospital.video-request.cancel';

    // Beauty
    public const BEAUTY_PROFILE_SHOW = 'beauty.profile.show';
    public const BEAUTY_PROFILE_UPDATE = 'beauty.profile.update';
    public const BEAUTY_PROFILE_DELETE = 'beauty.profile.delete';
    public const BEAUTY_MEMBERS_MANAGE = 'beauty.members.manage';

    public const BEAUTY_VIDEO_REQUEST_SHOW = 'beauty.video-request.show';
    public const BEAUTY_VIDEO_REQUEST_CREATE = 'beauty.video-request.create';
    public const BEAUTY_VIDEO_REQUEST_UPDATE = 'beauty.video-request.update';
    public const BEAUTY_VIDEO_REQUEST_CANCEL = 'beauty.video-request.cancel';

    // Agency
    public const AGENCY_PROFILE_SHOW = 'agency.profile.show';
    public const AGENCY_PROFILE_UPDATE = 'agency.profile.update';
    public const AGENCY_PROFILE_DELETE = 'agency.profile.delete';
    public const AGENCY_MEMBERS_MANAGE = 'agency.members.manage';

    // User
    public const USER_PROFILE_SHOW = 'user.profile.show';
    public const USER_PROFILE_UPDATE = 'user.profile.update';

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
            self::COMMON_ACCESS,
            self::COMMON_DASHBOARD_SHOW,
            self::COMMON_PROFILE_SHOW,
            self::COMMON_PROFILE_UPDATE,
        ];
    }

    /**
     * Staff(뷰랩) 전용 권한
     */
    public static function beaulab(): array
    {
        return [
            self::BEAULAB_HOSPITAL_SHOW,
            self::BEAULAB_HOSPITAL_CREATE,
            self::BEAULAB_HOSPITAL_UPDATE,
            self::BEAULAB_HOSPITAL_DELETE,
            self::BEAULAB_BEAUTY_SHOW,
            self::BEAULAB_BEAUTY_CREATE,
            self::BEAULAB_BEAUTY_UPDATE,
            self::BEAULAB_BEAUTY_DELETE,
            self::BEAULAB_AGENCY_SHOW,
            self::BEAULAB_AGENCY_CREATE,
            self::BEAULAB_AGENCY_UPDATE,
            self::BEAULAB_AGENCY_DELETE,
            self::BEAULAB_USER_SHOW,
            self::BEAULAB_USER_UPDATE,
            self::BEAULAB_USER_DELETE,
            self::BEAULAB_DOCTOR_SHOW,
            self::BEAULAB_DOCTOR_CREATE,
            self::BEAULAB_DOCTOR_UPDATE,
            self::BEAULAB_DOCTOR_DELETE,
            self::BEAULAB_EXPERT_SHOW,
            self::BEAULAB_EXPERT_CREATE,
            self::BEAULAB_EXPERT_UPDATE,
            self::BEAULAB_EXPERT_DELETE,
            self::BEAULAB_VIDEO_REQUEST_SHOW,
            self::BEAULAB_VIDEO_REQUEST_UPDATE,
            self::BEAULAB_VIDEO_REQUEST_DELETE,
        ];
    }

    /**
     * Partner - Hospital 권한
     */
    public static function hospital(): array
    {
        return [
            self::HOSPITAL_PROFILE_SHOW,
            self::HOSPITAL_PROFILE_UPDATE,
            self::HOSPITAL_PROFILE_DELETE,
            self::HOSPITAL_MEMBERS_MANAGE,
            self::HOSPITAL_VIDEO_REQUEST_SHOW,
            self::HOSPITAL_VIDEO_REQUEST_CREATE,
            self::HOSPITAL_VIDEO_REQUEST_UPDATE,
            self::HOSPITAL_VIDEO_REQUEST_CANCEL,
        ];
    }

    /**
     * Partner - Beauty 권한
     */
    public static function beauty(): array
    {
        return [
            self::BEAUTY_PROFILE_SHOW,
            self::BEAUTY_PROFILE_UPDATE,
            self::BEAUTY_PROFILE_DELETE,
            self::BEAUTY_MEMBERS_MANAGE,
            self::BEAUTY_VIDEO_REQUEST_SHOW,
            self::BEAUTY_VIDEO_REQUEST_CREATE,
            self::BEAUTY_VIDEO_REQUEST_UPDATE,
            self::BEAUTY_VIDEO_REQUEST_CANCEL,
        ];
    }

    /**
     * Partner - Agency 권한
     */
    public static function agency(): array
    {
        return [
            self::AGENCY_PROFILE_SHOW,
            self::AGENCY_PROFILE_UPDATE,
            self::AGENCY_PROFILE_DELETE,
            self::AGENCY_MEMBERS_MANAGE,
        ];
    }

    /**
     * User(앱) 권한
     */
    public static function user(): array
    {
        return [
            self::USER_PROFILE_SHOW,
            self::USER_PROFILE_UPDATE,
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
