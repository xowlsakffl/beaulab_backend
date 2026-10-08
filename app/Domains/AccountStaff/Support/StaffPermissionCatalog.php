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
        'harmful_word' => ['정책 설정', '금지어 설정'],
        'nickname' => ['정책 설정', '자동 닉네임'],
        'hospital' => ['병의원', '병의원'], 'doctor' => ['병의원', '의료진'],
        'hospital_entry' => ['병의원', '입점신청'], 'hospital_status_request' => ['병의원', '상태 변경 요청'],
        'hospital_account_invitation' => ['병의원', '계정 초대'], 'hospital_account_password_reset' => ['병의원', '비밀번호 재설정'],
        'hospital_wallet' => ['충전금', '충전금'],
        'hospital_event_db' => ['고객 DB', '이벤트 DB'], 'hospital_event_real_model_db' => ['고객 DB', '리얼모델 DB'],
        'remote_consultation' => ['고객 DB', '비대면 상담'],
        'hospital_event' => ['이벤트·광고', '이벤트'], 'hospital_event_ad' => ['이벤트·광고', '광고'],
        'video' => ['동영상', '동영상'], 'hospital_review' => ['게시물', '성형·쁘띠 후기'],
        'hospital_evaluation' => ['게시물', '병의원 평가'], 'talk' => ['게시물', '토크'],
        'reported_talk' => ['신고 게시물', '토크'], 'reported_hospital_review' => ['신고 게시물', '후기'],
        'reported_hospital_evaluation' => ['신고 게시물', '병의원 평가'], 'reported_chat_message' => ['신고 게시물', '채팅'],
        'reported_video' => ['신고 게시물', '동영상'],
        'user' => ['회원', '일반회원'], 'beauty' => ['회원', '뷰티'], 'agency' => ['회원', '대행사'], 'expert' => ['회원', '전문가'],
        'notice' => ['공지사항', '공지사항'], 'faq' => ['공지사항', 'FAQ'],
        'hospital_promotion' => ['프로모션', '병의원 프로모션'],
        'banner_exposure' => ['노출 설정', '배너·팝업'], 'event_exposure' => ['노출 설정', '이벤트'],
        'review_exposure' => ['노출 설정', '후기'], 'video_exposure' => ['노출 설정', '동영상'],
        'staff' => ['운영 관리', '직원 관리'], 'db_pricing' => ['운영 관리', 'DB 단가비용'], 'ad_pricing' => ['운영 관리', '광고비용'],
        'category' => ['운영 관리', '카테고리'], 'hashtag' => ['운영 관리', '해시태그'], 'internal_tool' => ['운영 관리', '내부 개발 도구'],
        'access' => ['공통 기능', '관리자 접속'], 'dashboard' => ['공통 기능', '대시보드'],
        'profile' => ['공통 기능', '내 정보'], 'admin_note' => ['공통 기능', '관리자메모'],
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

            return ['name' => $permission, 'group' => $group, 'menu' => $label, 'action' => self::ACTIONS[$action] ?? $action];
        }, self::names());
    }
}
