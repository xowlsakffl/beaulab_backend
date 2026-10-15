<?php

namespace App\Domains\AccountStaff\Support;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;

final class StaffPermissionCatalog
{
    public const ROLES = [
        AccessRoles::BEAULAB_SUPER_ADMIN => '총괄',
        AccessRoles::BEAULAB_ADMIN => '매니저',
        AccessRoles::BEAULAB_STAFF => '사원',
        AccessRoles::BEAULAB_DEV => '개발자',
    ];

    private const MENUS = [
        'hospital_chatbot_statistics' => ['챗봇', '챗봇 통계'],
        'hospital_chatbot_consultation' => ['챗봇', '상담'],
        'hospital_chatbot_qna' => ['챗봇', 'Q&A 관리'],
        'hospital_chatbot_operation' => ['챗봇', '상담 운영관리'],
        'harmful_word' => ['정책 설정', '금지어 설정'],
        'nickname' => ['정책 설정', '자동 닉네임'],
        'hospital' => ['병의원', '병의원'], 'doctor' => ['병의원', '의료진'],
        'hospital_entry' => ['병의원', '입점신청'], 'hospital_status_request' => ['병의원', '상태 변경 요청'],
        'hospital_account_invitation' => ['병의원', '계정 생성 초대'], 'hospital_account_password_reset' => ['병의원', '비밀번호 재설정'],
        'hospital_wallet' => ['충전금', '충전금'],
        'hospital_event_db' => ['고객 DB', '이벤트 DB'], 'hospital_event_real_model_db' => ['고객 DB', '리얼모델 DB'],
        'remote_consultation' => ['고객 DB', '비대면 상담'],
        'hospital_event' => ['이벤트·광고', '이벤트'], 'hospital_event_ad' => ['이벤트·광고', '광고'],
        'video' => ['동영상', '동영상'], 'hospital_review' => ['게시물', '성형·쁘띠 후기'],
        'hospital_evaluation' => ['게시물', '병의원 평가'], 'talk' => ['게시물', '토크'],
        'reported_talk' => ['신고 게시물', '토크'], 'reported_hospital_review' => ['신고 게시물', '후기'],
        'reported_hospital_evaluation' => ['신고 게시물', '병의원 평가'], 'reported_chat_message' => ['신고 게시물', '채팅'],
        'reported_video' => ['신고 게시물', '동영상'],
        'user' => ['회원', '일반회원'], 'beauty' => ['회원', '뷰티'], 'expert' => ['회원', '전문가'],
        'notice' => ['공지사항', '공지사항'],
        'hospital_promotion' => ['프로모션', '병의원 프로모션'],
        'banner_exposure' => ['노출 설정', '배너·팝업'], 'event_exposure' => ['노출 설정', '이벤트'],
        'other_exposure' => ['노출 설정', '기타'],
        'review_exposure' => ['노출 설정', '후기'], 'video_exposure' => ['노출 설정', '동영상'],
        'staff' => ['운영 관리', '직원 관리'], 'db_pricing' => ['운영 관리', 'DB 단가비용'], 'ad_pricing' => ['운영 관리', '광고비용'],
        'category' => ['운영 관리', '카테고리'], 'hashtag' => ['운영 관리', '해시태그'], 'internal_tool' => ['운영 관리', '내부 개발 도구'],
        'dashboard' => ['공통 기능', '대시보드'], 'admin_note' => ['공통 기능', '관리자메모'],
    ];

    private const ACTIONS = [
        'show' => '조회', 'create' => '등록', 'update' => '수정', 'delete' => '삭제', 'status_update' => '상태변경',
        'approve' => '승인', 'process' => '처리', 'manage' => '관리', 'permissions_manage' => '권한 관리',
        'write' => '작성·수정', 'send' => '발송', 'history_show' => '거래내역 조회', 'service_grant' => '서비스 지급',
        'service_reclaim' => '서비스 회수', 'refund_request' => '환불 신청', 'refund_document_show' => '환불 서류 조회',
        'refund_process' => '환불 처리', 'notice_show' => '충전 안내 조회', 'notice_send' => '충전 안내 발송', 'cancel' => '취소',
    ];

    public static function names(): array
    {
        return AccessPermissions::byGuard()[AccessPermissions::GUARD_STAFF];
    }

    public static function activeNames(array $names): array
    {
        return array_values(array_unique(array_intersect($names, self::names())));
    }

    public static function items(): array
    {
        return array_map(static function (string $permission): array {
            $parts = explode('.', $permission);
            $menu = $parts[1];
            $action = $parts[2] ?? 'show';
            [$group, $label] = self::MENUS[$menu] ?? ['기타 기능', $menu];
            if ($parts[0] !== 'beaulab' && $parts[0] !== 'common') {
                $group = $parts[0] === 'hospital' ? '병의원 전용 기능' : '뷰티 전용 기능';
            }

            $actionLabel = match ($permission) {
                AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS => '상담 처리',
                AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_UPDATE => '발송',
                AccessPermissions::BEAULAB_REMOTE_CONSULTATION_APPROVE => '상태변경',
                AccessPermissions::BEAULAB_REMOTE_CONSULTATION_ASSIGN => '직접 배정',
                AccessPermissions::BEAULAB_HOSPITAL_WALLET_DASHBOARD_SHOW,
                AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_CALENDAR_SHOW,
                AccessPermissions::BEAULAB_VIDEO_CONSENT_SHOW,
                AccessPermissions::BEAULAB_USER_STATISTICS_SHOW,
                AccessPermissions::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW => '조회',
                default => self::ACTIONS[$action] ?? $action,
            };

            if ($menu === 'hospital_wallet') {
                $label = match ($permission) {
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_DASHBOARD_SHOW => '충전금 현황',
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_DOCUMENT_SHOW,
                    AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS => '충전금 내역',
                    default => '병의원 충전금 관리',
                };
            }

            if ($menu === 'hospital_event_ad') {
                $label = $permission === AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_CALENDAR_SHOW ? '광고 현황' : '광고 관리';
            }

            if ($menu === 'video') {
                $label = $permission === AccessPermissions::BEAULAB_VIDEO_CONSENT_SHOW ? '영상 동의여부' : '영상 등록관리';
            }
            if ($menu === 'user') {
                $label = $permission === AccessPermissions::BEAULAB_USER_STATISTICS_SHOW ? '일반회원 현황' : '일반회원';
            }

            return ['name' => $permission, 'group' => $group, 'menu' => $label, 'action' => $actionLabel];
        }, self::names());
    }
}
