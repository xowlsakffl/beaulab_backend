<?php
// app/Common/Authorization/AdminPermissions.php

namespace App\Modules\Admin\Authorization;

final class AdminPermissions
{
    public const GUARD = 'admin';

    /**
     * 프로젝트 전체 admin permission 목록
     * 도메인 늘어나면 여기만 추가
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            self::common(),
            self::beaulab(),
            self::hospital(),
            self::beauty(),
            self::agency(),
        )));
    }

    /**
     * 공통 권한
     */
    public static function common(): array
    {
        return [
            // 공통

            // 관리자 영역 기본
            'common.access',

            // 기본 화면/계정
            'common.dashboard.show',
            'common.profile.show',
            'common.profile.update',
        ];
    }

    /**
     * 뷰랩 전용 권한
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
     * 병원 권한
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
     * 뷰티 권한
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
     * 대행사 권한
     */
    public static function agency(): array
    {
        return [
            'agency.profile.show',
            'agency.profile.update',
            'agency.members.manage',
        ];
    }
}
