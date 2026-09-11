<?php

namespace App\Common\Authorization;

final class AccessPermissions
{
    public const GUARD_STAFF = 'staff';

    public const GUARD_HOSPITAL = 'hospital';

    public const GUARD_BEAUTY = 'beauty';

    // Common
    public const COMMON_ACCESS = 'common.access';

    public const COMMON_DASHBOARD_SHOW = 'common.dashboard.show';

    public const COMMON_PROFILE_SHOW = 'common.profile.show';

    public const COMMON_PROFILE_UPDATE = 'common.profile.update';

    public const COMMON_ADMIN_NOTE_WRITE = 'common.admin_note.write';

    // Beaulab
    public const BEAULAB_HOSPITAL_SHOW = 'beaulab.hospital.show';

    public const BEAULAB_HOSPITAL_CREATE = 'beaulab.hospital.create';

    public const BEAULAB_HOSPITAL_UPDATE = 'beaulab.hospital.update';

    public const BEAULAB_HOSPITAL_STATUS_UPDATE = 'beaulab.hospital.status_update';

    public const BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW = 'beaulab.hospital_status_request.show';

    public const BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE = 'beaulab.hospital_status_request.create';

    public const BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS = 'beaulab.hospital_status_request.process';

    public const BEAULAB_HOSPITAL_DELETE = 'beaulab.hospital.delete';

    public const BEAULAB_HOSPITAL_WALLET_SHOW = 'beaulab.hospital_wallet.show';

    public const BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW = 'beaulab.hospital_wallet.history_show';

    public const BEAULAB_HOSPITAL_WALLET_SERVICE_GRANT = 'beaulab.hospital_wallet.service_grant';

    public const BEAULAB_HOSPITAL_WALLET_SERVICE_RECLAIM = 'beaulab.hospital_wallet.service_reclaim';

    public const BEAULAB_HOSPITAL_WALLET_REFUND_REQUEST = 'beaulab.hospital_wallet.refund_request';

    public const BEAULAB_HOSPITAL_WALLET_REFUND_DOCUMENT_SHOW = 'beaulab.hospital_wallet.refund_document_show';

    public const BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS = 'beaulab.hospital_wallet.refund_process';

    public const BEAULAB_HOSPITAL_WALLET_NOTICE_SHOW = 'beaulab.hospital_wallet.notice_show';

    public const BEAULAB_HOSPITAL_WALLET_NOTICE_SEND = 'beaulab.hospital_wallet.notice_send';

    public const BEAULAB_HOSPITAL_ENTRY_SHOW = 'beaulab.hospital_entry.show';

    public const BEAULAB_HOSPITAL_ENTRY_UPDATE = 'beaulab.hospital_entry.update';

    public const BEAULAB_HOSPITAL_ENTRY_STATUS_UPDATE = 'beaulab.hospital_entry.status_update';

    public const BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW = 'beaulab.hospital_account_invitation.show';

    public const BEAULAB_HOSPITAL_ACCOUNT_INVITATION_UPDATE = 'beaulab.hospital_account_invitation.update';

    public const BEAULAB_HOSPITAL_ACCOUNT_PASSWORD_RESET_SEND = 'beaulab.hospital_account_password_reset.send';

    public const BEAULAB_BEAUTY_SHOW = 'beaulab.beauty.show';

    public const BEAULAB_BEAUTY_CREATE = 'beaulab.beauty.create';

    public const BEAULAB_BEAUTY_UPDATE = 'beaulab.beauty.update';

    public const BEAULAB_BEAUTY_DELETE = 'beaulab.beauty.delete';

    public const BEAULAB_AGENCY_SHOW = 'beaulab.agency.show';

    public const BEAULAB_AGENCY_CREATE = 'beaulab.agency.create';

    public const BEAULAB_AGENCY_UPDATE = 'beaulab.agency.update';

    public const BEAULAB_AGENCY_DELETE = 'beaulab.agency.delete';

    public const BEAULAB_USER_SHOW = 'beaulab.user.show';

    public const BEAULAB_USER_STATUS_UPDATE = 'beaulab.user.status_update';

    public const BEAULAB_STAFF_SHOW = 'beaulab.staff.show';

    public const BEAULAB_STAFF_CREATE = 'beaulab.staff.create';

    public const BEAULAB_STAFF_UPDATE = 'beaulab.staff.update';

    public const BEAULAB_STAFF_DELETE = 'beaulab.staff.delete';

    public const BEAULAB_DB_PRICING_MANAGE = 'beaulab.db_pricing.manage';

    public const BEAULAB_AD_PRICING_MANAGE = 'beaulab.ad_pricing.manage';

    public const BEAULAB_DOCTOR_SHOW = 'beaulab.doctor.show';

    public const BEAULAB_DOCTOR_CREATE = 'beaulab.doctor.create';

    public const BEAULAB_DOCTOR_UPDATE = 'beaulab.doctor.update';

    public const BEAULAB_DOCTOR_STATUS_UPDATE = 'beaulab.doctor.status_update';

    public const BEAULAB_DOCTOR_DELETE = 'beaulab.doctor.delete';

    public const BEAULAB_EXPERT_SHOW = 'beaulab.expert.show';

    public const BEAULAB_EXPERT_CREATE = 'beaulab.expert.create';

    public const BEAULAB_EXPERT_UPDATE = 'beaulab.expert.update';

    public const BEAULAB_EXPERT_STATUS_UPDATE = 'beaulab.expert.status_update';

    public const BEAULAB_EXPERT_DELETE = 'beaulab.expert.delete';

    public const BEAULAB_VIDEO_SHOW = 'beaulab.video.show';

    public const BEAULAB_VIDEO_CREATE = 'beaulab.video.create';

    public const BEAULAB_VIDEO_UPDATE = 'beaulab.video.update';

    public const BEAULAB_VIDEO_STATUS_UPDATE = 'beaulab.video.status_update';

    public const BEAULAB_VIDEO_DELETE = 'beaulab.video.delete';

    public const BEAULAB_HOSPITAL_EVENT_SHOW = 'beaulab.hospital_event.show';

    public const BEAULAB_HOSPITAL_EVENT_CREATE = 'beaulab.hospital_event.create';

    public const BEAULAB_HOSPITAL_EVENT_UPDATE = 'beaulab.hospital_event.update';

    public const BEAULAB_HOSPITAL_EVENT_STATUS_UPDATE = 'beaulab.hospital_event.status_update';

    public const BEAULAB_HOSPITAL_EVENT_DELETE = 'beaulab.hospital_event.delete';

    public const BEAULAB_HOSPITAL_EVENT_AD_SHOW = 'beaulab.hospital_event_ad.show';

    public const BEAULAB_HOSPITAL_EVENT_AD_CREATE = 'beaulab.hospital_event_ad.create';

    public const BEAULAB_HOSPITAL_EVENT_AD_UPDATE = 'beaulab.hospital_event_ad.update';

    public const BEAULAB_HOSPITAL_EVENT_AD_STATUS_UPDATE = 'beaulab.hospital_event_ad.status_update';

    public const BEAULAB_HOSPITAL_EVENT_AD_DELETE = 'beaulab.hospital_event_ad.delete';

    public const BEAULAB_HOSPITAL_EVENT_DB_SHOW = 'beaulab.hospital_event_db.show';

    public const BEAULAB_HOSPITAL_EVENT_DB_UPDATE = 'beaulab.hospital_event_db.update';

    public const BEAULAB_HOSPITAL_EVENT_DB_STATUS_UPDATE = 'beaulab.hospital_event_db.status_update';

    public const BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW = 'beaulab.hospital_event_real_model_db.show';

    public const BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_UPDATE = 'beaulab.hospital_event_real_model_db.update';

    public const BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_STATUS_UPDATE = 'beaulab.hospital_event_real_model_db.status_update';

    public const BEAULAB_HOSPITAL_REVIEW_SHOW = 'beaulab.hospital_review.show';

    public const BEAULAB_HOSPITAL_REVIEW_UPDATE = 'beaulab.hospital_review.update';

    public const BEAULAB_HOSPITAL_REVIEW_STATUS_UPDATE = 'beaulab.hospital_review.status_update';

    public const BEAULAB_HOSPITAL_EVALUATION_SHOW = 'beaulab.hospital_evaluation.show';

    public const BEAULAB_HOSPITAL_EVALUATION_UPDATE = 'beaulab.hospital_evaluation.update';

    public const BEAULAB_HOSPITAL_EVALUATION_STATUS_UPDATE = 'beaulab.hospital_evaluation.status_update';

    public const BEAULAB_TALK_SHOW = 'beaulab.talk.show';

    public const BEAULAB_TALK_UPDATE = 'beaulab.talk.update';

    public const BEAULAB_TALK_STATUS_UPDATE = 'beaulab.talk.status_update';

    public const BEAULAB_REPORTED_TALK_SHOW = 'beaulab.reported_talk.show';

    public const BEAULAB_REPORTED_TALK_UPDATE = 'beaulab.reported_talk.update';

    public const BEAULAB_REPORTED_TALK_STATUS_UPDATE = 'beaulab.reported_talk.status_update';

    public const BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW = 'beaulab.reported_hospital_review.show';

    public const BEAULAB_REPORTED_HOSPITAL_REVIEW_UPDATE = 'beaulab.reported_hospital_review.update';

    public const BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE = 'beaulab.reported_hospital_review.status_update';

    public const BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW = 'beaulab.reported_hospital_evaluation.show';

    public const BEAULAB_REPORTED_HOSPITAL_EVALUATION_UPDATE = 'beaulab.reported_hospital_evaluation.update';

    public const BEAULAB_REPORTED_HOSPITAL_EVALUATION_STATUS_UPDATE = 'beaulab.reported_hospital_evaluation.status_update';

    public const BEAULAB_REPORTED_CHAT_MESSAGE_SHOW = 'beaulab.reported_chat_message.show';

    public const BEAULAB_REPORTED_CHAT_MESSAGE_UPDATE = 'beaulab.reported_chat_message.update';

    public const BEAULAB_REPORTED_CHAT_MESSAGE_STATUS_UPDATE = 'beaulab.reported_chat_message.status_update';

    public const BEAULAB_REPORTED_VIDEO_SHOW = 'beaulab.reported_video.show';

    public const BEAULAB_REPORTED_VIDEO_UPDATE = 'beaulab.reported_video.update';

    public const BEAULAB_REPORTED_VIDEO_STATUS_UPDATE = 'beaulab.reported_video.status_update';

    public const BEAULAB_HOSPITAL_PROMOTION_SHOW = 'beaulab.hospital_promotion.show';

    public const BEAULAB_HOSPITAL_PROMOTION_CREATE = 'beaulab.hospital_promotion.create';

    public const BEAULAB_HOSPITAL_PROMOTION_UPDATE = 'beaulab.hospital_promotion.update';

    public const BEAULAB_HOSPITAL_PROMOTION_STATUS_UPDATE = 'beaulab.hospital_promotion.status_update';

    public const BEAULAB_NOTICE_SHOW = 'beaulab.notice.show';

    public const BEAULAB_NOTICE_CREATE = 'beaulab.notice.create';

    public const BEAULAB_NOTICE_UPDATE = 'beaulab.notice.update';

    public const BEAULAB_NOTICE_STATUS_UPDATE = 'beaulab.notice.status_update';

    public const BEAULAB_NOTICE_DELETE = 'beaulab.notice.delete';

    public const BEAULAB_FAQ_SHOW = 'beaulab.faq.show';

    public const BEAULAB_FAQ_CREATE = 'beaulab.faq.create';

    public const BEAULAB_FAQ_UPDATE = 'beaulab.faq.update';

    public const BEAULAB_FAQ_STATUS_UPDATE = 'beaulab.faq.status_update';

    public const BEAULAB_FAQ_DELETE = 'beaulab.faq.delete';

    public const BEAULAB_CATEGORY_MANAGE = 'beaulab.category.manage';

    public const BEAULAB_CATEGORY_STATUS_UPDATE = 'beaulab.category.status_update';

    public const BEAULAB_HASHTAG_MANAGE = 'beaulab.hashtag.manage';

    public const BEAULAB_HASHTAG_STATUS_UPDATE = 'beaulab.hashtag.status_update';

    // Hospital
    public const HOSPITAL_PROFILE_SHOW = 'hospital.profile.show';

    public const HOSPITAL_PROFILE_UPDATE = 'hospital.profile.update';

    public const HOSPITAL_PROFILE_DELETE = 'hospital.profile.delete';

    public const HOSPITAL_VIDEO_SHOW = 'hospital.video.show';

    public const HOSPITAL_VIDEO_UPDATE = 'hospital.video.update';

    // Beauty
    public const BEAUTY_PROFILE_SHOW = 'beauty.profile.show';

    public const BEAUTY_PROFILE_UPDATE = 'beauty.profile.update';

    public const BEAUTY_PROFILE_DELETE = 'beauty.profile.delete';

    public const BEAUTY_MEMBERS_MANAGE = 'beauty.members.manage';

    public const BEAUTY_VIDEO_SHOW = 'beauty.video.show';

    public const BEAUTY_VIDEO_CREATE = 'beauty.video.create';

    public const BEAUTY_VIDEO_UPDATE = 'beauty.video.update';

    public const BEAUTY_VIDEO_CANCEL = 'beauty.video.cancel';

    /**
     * guard별 생성해야 할 permission 목록 (Seeder는 이걸 기준으로 생성)
     *
     * - staff: 내부 직원(뷰랩)
     * - hospital: 병원 파트너
     * - beauty: 뷰티 파트너
     * - user: 일반 사용자(앱)는 Spatie permission을 사용하지 않는다.
     */
    public static function byGuard(): array
    {
        return [
            self::GUARD_STAFF => self::unique([
                ...self::common(),   // staff/partner 공통(관리영역)
                ...self::beaulab(),  // staff 전용
                ...self::beaulabSuperAdminOnly(), // super admin 전용
            ]),

            self::GUARD_HOSPITAL => self::unique([
                ...self::common(),
                ...self::hospital(),
            ]),

            self::GUARD_BEAUTY => self::unique([
                ...self::common(),
                ...self::beauty(),
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
            self::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW,
            self::BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE,
            self::BEAULAB_HOSPITAL_DELETE,
            self::BEAULAB_HOSPITAL_WALLET_SHOW,
            self::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW,
            self::BEAULAB_HOSPITAL_WALLET_SERVICE_GRANT,
            self::BEAULAB_HOSPITAL_WALLET_SERVICE_RECLAIM,
            self::BEAULAB_HOSPITAL_WALLET_REFUND_REQUEST,
            self::BEAULAB_HOSPITAL_WALLET_REFUND_DOCUMENT_SHOW,
            self::BEAULAB_HOSPITAL_WALLET_NOTICE_SHOW,
            self::BEAULAB_HOSPITAL_WALLET_NOTICE_SEND,
            self::BEAULAB_HOSPITAL_ENTRY_SHOW,
            self::BEAULAB_HOSPITAL_ENTRY_UPDATE,
            self::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW,
            self::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_UPDATE,
            self::BEAULAB_HOSPITAL_ACCOUNT_PASSWORD_RESET_SEND,

            self::BEAULAB_BEAUTY_SHOW,
            self::BEAULAB_BEAUTY_CREATE,
            self::BEAULAB_BEAUTY_UPDATE,
            self::BEAULAB_BEAUTY_DELETE,

            self::BEAULAB_AGENCY_SHOW,
            self::BEAULAB_AGENCY_CREATE,
            self::BEAULAB_AGENCY_UPDATE,
            self::BEAULAB_AGENCY_DELETE,

            self::BEAULAB_USER_SHOW,

            self::BEAULAB_DOCTOR_SHOW,
            self::BEAULAB_DOCTOR_CREATE,
            self::BEAULAB_DOCTOR_UPDATE,
            self::BEAULAB_DOCTOR_DELETE,

            self::BEAULAB_EXPERT_SHOW,
            self::BEAULAB_EXPERT_CREATE,
            self::BEAULAB_EXPERT_UPDATE,
            self::BEAULAB_EXPERT_DELETE,

            self::BEAULAB_VIDEO_SHOW,
            self::BEAULAB_VIDEO_CREATE,
            self::BEAULAB_VIDEO_UPDATE,
            self::BEAULAB_VIDEO_DELETE,
            self::BEAULAB_HOSPITAL_EVENT_SHOW,
            self::BEAULAB_HOSPITAL_EVENT_CREATE,
            self::BEAULAB_HOSPITAL_EVENT_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_DELETE,
            self::BEAULAB_HOSPITAL_EVENT_AD_SHOW,
            self::BEAULAB_HOSPITAL_EVENT_AD_CREATE,
            self::BEAULAB_HOSPITAL_EVENT_AD_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_AD_DELETE,
            self::BEAULAB_HOSPITAL_EVENT_DB_SHOW,
            self::BEAULAB_HOSPITAL_EVENT_DB_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW,
            self::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_UPDATE,
            self::BEAULAB_HOSPITAL_REVIEW_SHOW,
            self::BEAULAB_HOSPITAL_REVIEW_UPDATE,
            self::BEAULAB_HOSPITAL_EVALUATION_SHOW,
            self::BEAULAB_HOSPITAL_EVALUATION_UPDATE,
            self::BEAULAB_TALK_SHOW,
            self::BEAULAB_TALK_UPDATE,
            self::BEAULAB_REPORTED_TALK_SHOW,
            self::BEAULAB_REPORTED_TALK_UPDATE,
            self::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
            self::BEAULAB_REPORTED_HOSPITAL_REVIEW_UPDATE,
            self::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW,
            self::BEAULAB_REPORTED_HOSPITAL_EVALUATION_UPDATE,
            self::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW,
            self::BEAULAB_REPORTED_CHAT_MESSAGE_UPDATE,
            self::BEAULAB_REPORTED_VIDEO_SHOW,
            self::BEAULAB_REPORTED_VIDEO_UPDATE,
            self::BEAULAB_HOSPITAL_PROMOTION_SHOW,
            self::BEAULAB_HOSPITAL_PROMOTION_CREATE,
            self::BEAULAB_HOSPITAL_PROMOTION_UPDATE,
            self::BEAULAB_NOTICE_SHOW,
            self::BEAULAB_NOTICE_CREATE,
            self::BEAULAB_NOTICE_UPDATE,
            self::BEAULAB_NOTICE_DELETE,
            self::BEAULAB_FAQ_SHOW,
            self::BEAULAB_FAQ_CREATE,
            self::BEAULAB_FAQ_UPDATE,
            self::BEAULAB_FAQ_DELETE,

            self::BEAULAB_CATEGORY_MANAGE,
            self::BEAULAB_HASHTAG_MANAGE,
            self::COMMON_ADMIN_NOTE_WRITE,

        ];
    }

    /**
     * Staff 중 super_admin에게만 부여할 권한
     */
    public static function beaulabSuperAdminOnly(): array
    {
        return [
            self::BEAULAB_STAFF_SHOW,
            self::BEAULAB_STAFF_CREATE,
            self::BEAULAB_STAFF_UPDATE,
            self::BEAULAB_STAFF_DELETE,
            self::BEAULAB_DB_PRICING_MANAGE,
            self::BEAULAB_AD_PRICING_MANAGE,
            self::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS,
            self::BEAULAB_HOSPITAL_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS,
            self::BEAULAB_HOSPITAL_ENTRY_STATUS_UPDATE,
            self::BEAULAB_USER_STATUS_UPDATE,
            self::BEAULAB_DOCTOR_STATUS_UPDATE,
            self::BEAULAB_EXPERT_STATUS_UPDATE,
            self::BEAULAB_VIDEO_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_AD_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_DB_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_REVIEW_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_EVALUATION_STATUS_UPDATE,
            self::BEAULAB_TALK_STATUS_UPDATE,
            self::BEAULAB_REPORTED_TALK_STATUS_UPDATE,
            self::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE,
            self::BEAULAB_REPORTED_HOSPITAL_EVALUATION_STATUS_UPDATE,
            self::BEAULAB_REPORTED_CHAT_MESSAGE_STATUS_UPDATE,
            self::BEAULAB_REPORTED_VIDEO_STATUS_UPDATE,
            self::BEAULAB_HOSPITAL_PROMOTION_STATUS_UPDATE,
            self::BEAULAB_NOTICE_STATUS_UPDATE,
            self::BEAULAB_FAQ_STATUS_UPDATE,
            self::BEAULAB_CATEGORY_STATUS_UPDATE,
            self::BEAULAB_HASHTAG_STATUS_UPDATE,
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
            self::HOSPITAL_VIDEO_SHOW,
            self::HOSPITAL_VIDEO_UPDATE,
            self::COMMON_ADMIN_NOTE_WRITE,
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
            self::BEAUTY_VIDEO_SHOW,
            self::BEAUTY_VIDEO_CREATE,
            self::BEAUTY_VIDEO_UPDATE,
            self::BEAUTY_VIDEO_CANCEL,
            self::COMMON_ADMIN_NOTE_WRITE,
        ];
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private static function unique(array $permissions): array
    {
        return array_values(array_unique($permissions));
    }
}
