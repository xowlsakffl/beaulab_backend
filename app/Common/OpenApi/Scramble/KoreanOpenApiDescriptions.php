<?php

namespace App\Common\OpenApi\Scramble;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class KoreanOpenApiDescriptions
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $columnDescriptions = null;

    /**
     * @var array<string, array<string, string>>|null
     */
    private static ?array $requestAttributesBySchemaName = null;

    /**
     * @param  array<string, string>  $requestAttributes
     */
    public static function field(string $name, array $requestAttributes = []): ?string
    {
        $name = self::normalizeFieldName($name);

        $attribute = self::attributeDescription($name, $requestAttributes);
        if ($attribute !== null) {
            return $attribute;
        }

        return self::manualFieldDescriptions()[$name]
            ?? self::columnDescriptions()[$name]
            ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function requestAttributesForClass(?string $className): array
    {
        if (! is_string($className) || ! class_exists($className) || ! is_subclass_of($className, FormRequest::class)) {
            return [];
        }

        try {
            $request = new $className;
            if (! method_exists($request, 'attributes')) {
                return [];
            }

            return self::normalizeAttributes($request->attributes());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function requestAttributesBySchemaName(): array
    {
        if (self::$requestAttributesBySchemaName !== null) {
            return self::$requestAttributesBySchemaName;
        }

        $attributes = [];
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return self::$requestAttributesBySchemaName = [];
        }

        foreach (File::allFiles($modulesPath) as $file) {
            if (! str_ends_with($file->getFilename(), 'Request.php')) {
                continue;
            }

            $relativePath = str_replace('\\', '/', $file->getPathname());
            $appPath = str_replace('\\', '/', app_path()).'/';

            if (! str_starts_with($relativePath, $appPath)) {
                continue;
            }

            $relativeClass = substr($relativePath, strlen($appPath), -4);
            $className = 'App\\'.str_replace('/', '\\', $relativeClass);
            $classAttributes = self::requestAttributesForClass($className);

            if ($classAttributes === []) {
                continue;
            }

            $attributes[class_basename($className)] = $classAttributes;
        }

        return self::$requestAttributesBySchemaName = $attributes;
    }

    /**
     * @return array<string, string>
     */
    private static function columnDescriptions(): array
    {
        if (self::$columnDescriptions !== null) {
            return self::$columnDescriptions;
        }

        try {
            $rows = DB::select(
                <<<'SQL'
                select column_name as name, column_comment as comment
                from information_schema.columns
                where table_schema = schema()
                  and column_comment is not null
                  and column_comment <> ''
                SQL
            );
        } catch (\Throwable) {
            return self::$columnDescriptions = [];
        }

        $commentsByColumn = [];

        foreach ($rows as $row) {
            $column = self::normalizeFieldName((string) $row->name);
            $comment = trim((string) $row->comment);

            if ($column === '' || $comment === '') {
                continue;
            }

            $commentsByColumn[$column][$comment] = $comment;
        }

        $descriptions = [];

        foreach ($commentsByColumn as $column => $comments) {
            $comments = array_values($comments);

            if (count($comments) === 1) {
                $descriptions[$column] = $comments[0];

                continue;
            }

            $normalized = array_unique(array_map(self::normalizeKoreanText(...), $comments));
            if (count($normalized) === 1) {
                $descriptions[$column] = $comments[0];
            }
        }

        return self::$columnDescriptions = $descriptions;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    private static function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                continue;
            }

            $field = self::normalizeFieldName($key);
            $description = trim($value);

            if ($field !== '' && $description !== '' && ! array_key_exists($field, $normalized)) {
                $normalized[$field] = $description;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $requestAttributes
     */
    private static function attributeDescription(string $name, array $requestAttributes): ?string
    {
        if (array_key_exists($name, $requestAttributes)) {
            return $requestAttributes[$name];
        }

        $wildcard = $name.'.*';
        if (array_key_exists($wildcard, $requestAttributes)) {
            return $requestAttributes[$wildcard];
        }

        return null;
    }

    private static function normalizeFieldName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\[\]$/', '', $name) ?? $name;
        $name = preg_replace('/\.\*$/', '', $name) ?? $name;

        return $name;
    }

    private static function normalizeKoreanText(string $value): string
    {
        return preg_replace('/[\s\-_()\/]+/u', '', mb_strtolower($value)) ?? $value;
    }

    /**
     * @return array<string, string>
     */
    private static function manualFieldDescriptions(): array
    {
        return [
            'success' => '요청 성공 여부',
            'data' => '응답 데이터',
            'meta' => '페이지네이션 등 부가 정보',
            'traceId' => '요청 추적 ID',
            'current_page' => '현재 페이지 번호',
            'per_page' => '페이지당 항목 수',
            'total' => '전체 항목 수',
            'last_page' => '마지막 페이지 번호',
            'q' => '검색어',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지 번호',
            'include' => '응답에 포함할 연관 데이터',
            'date_type' => '기간 검색 기준',
            'date_types' => '기간 검색 기준 목록',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
            'month' => '조회 월',
            'id' => '고유 ID',
            'ids' => '고유 ID 목록',
            'created_at' => '생성 일시',
            'updated_at' => '수정 일시',
            'deleted_at' => '삭제 일시',
            'author' => '작성자 정보',
            'author_id' => '작성자 ID',
            'nickname' => '닉네임',
            'category_code' => '카테고리 코드',
            'category_codes' => '카테고리 코드 목록',
            'category_domain' => '카테고리 도메인',
            'category_id' => '카테고리 ID',
            'category_ids' => '카테고리 ID 목록',
            'categories' => '카테고리 목록',
            'domain' => '카테고리 도메인',
            'usage' => '카테고리 사용처',
            'group' => '카테고리 그룹',
            'group_code' => '카테고리 그룹 코드',
            'depth' => '카테고리 깊이',
            'parent_code' => '상위 카테고리 코드',
            'feature_ids' => '병의원 특징 ID 목록',
            'image_ids' => '이미지 ID 목록',
            'images' => '이미지 목록',
            'receipt_images' => '영수증 이미지 목록',
            'poll' => '투표 정보',
            'allow_multiple' => '복수 선택 허용 여부',
            'options' => '선택 항목 목록',
            'option_ids' => '선택한 투표 항목 ID 목록',
            'my_voted_option_ids' => '내가 선택한 투표 항목 ID 목록',
            'gallery' => '대표/내부 이미지 목록',
            'gallery_order' => '이미지 정렬 순서',
            'existing_gallery_ids' => '유지할 기존 이미지 ID 목록',
            'existing_logo_id' => '유지할 기존 로고 파일 ID',
            'existing_business_registration_file_id' => '유지할 기존 사업자등록증 파일 ID',
            'logo' => '로고 이미지 파일',
            'business_registration_file' => '사업자등록증 파일',
            'bankbook_file' => '통장 사본 파일',
            'supply_amount' => '공급가액',
            'vat_amount' => '부가세',
            'refund_amount' => '최종 환불 금액',
            'bank_name' => '환불 은행',
            'account_number' => '환불 계좌번호',
            'rejection_reason' => '반려 사유',
            'operation_histories' => '운영 처리 이력',
            'comments' => '댓글 목록',
            'parent_id' => '부모 ID',
            'parent_talk_title' => '부모 토크 제목',
            'talk_id' => '토크 ID',
            'title' => '제목',
            'content' => '내용',
            'status' => '상태',
            'post_status' => '게시 상태',
            'allow_status' => '검수 상태',
            'allow_statuses' => '검수 상태 목록',
            'admin_status' => '관리자 강제중지 상태',
            'account_status' => '계정 상태',
            'hospital_status' => '병의원 공개여부',
            'ad_status' => '광고 상태',
            'report_status' => '신고 처리 상태',
            'warning_status' => '경고 여부',
            'statuses' => '상태 목록',
            'is_pinned' => '상단 고정 여부',
            'pinned_order' => '상단 고정 정렬 순서',
            'view_count' => '조회수',
            'view_count_min' => '최소 조회수',
            'view_count_max' => '최대 조회수',
            'like_count_min' => '최소 좋아요 수',
            'like_count_max' => '최대 좋아요 수',
            'report_count_min' => '최소 신고 수',
            'report_count_max' => '최대 신고 수',
            'warning_count_min' => '최소 경고 수',
            'warning_count_max' => '최대 경고 수',
            'comment_count' => '댓글 수',
            'like_count' => '좋아요 수',
            'vote_count' => '투표 수',
            'is_voted' => '내 선택 여부',
            'sort_order' => '정렬 순서',
            'save_count' => '저장 수',
            'name' => '이름/명칭',
            'email' => '이메일',
            'password' => '비밀번호',
            'current_password' => '현재 비밀번호',
            'new_password' => '새 비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'token' => '인증 토큰',
            'token_type' => '토큰 타입',
            'expires_at' => '만료 일시',
            'keep_logged_in' => '로그인 유지 여부',
            'available' => '사용 가능 여부',
            'exists' => '중복 존재 여부',
            'message' => '메시지',
            'errors' => '검증 오류 상세',
            'file' => '업로드 파일',
            'files' => '업로드 파일 목록',
            'attachments' => '첨부 파일 목록',
            'existing_attachment_ids' => '유지할 기존 첨부 파일 ID 목록',
            'url' => 'URL',
            'urls' => 'URL 목록',
            'path' => '파일 경로',
            'paths' => '파일 경로 목록',
            'mime_type' => 'MIME 타입',
            'size' => '파일 크기',
            'width' => '이미지 너비',
            'height' => '이미지 높이',
            'hospital' => '병의원 정보',
            'hospital_entry' => '입점신청 정보',
            'hospital_id' => '병의원 ID',
            'hospital_name' => '병의원명',
            'hospital_phone' => '병의원 전화번호',
            'doctor' => '의료진 정보',
            'doctor_id' => '의료진 ID',
            'address_detail' => '상세주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'business_number' => '사업자등록번호',
            'business_address' => '사업자 주소',
            'business_address_detail' => '사업자 상세주소',
            'business_type' => '업태',
            'business_item' => '종목',
            'ceo_name' => '대표자명',
            'license_number' => '면허번호',
            'license_file' => '면허증 파일',
            'applicant_name' => '신청자 이름',
            'applicant_position' => '신청자 직책',
            'applicant_phone' => '신청자 전화번호',
            'applicant_email' => '신청자 이메일주소',
            'phone' => '전화번호',
            'tel' => '전화번호',
            'tax_invoice_email' => '세금계산서 이메일',
            'settlement_bank_name' => '정산 은행명',
            'settlement_account_number' => '정산 계좌번호',
            'settlement_account_holder' => '정산 예금주',
            'blog_url' => '블로그 URL',
            'instagram_url' => '인스타그램 URL',
            'youtube_link' => '유튜브 링크',
            'cost' => '비용',
            'cost_min' => '최소 비용',
            'cost_max' => '최대 비용',
            'normal_price' => '정상가',
            'event_price' => '이벤트가',
            'event_price_min' => '최소 이벤트가',
            'event_price_max' => '최대 이벤트가',
            'consultation_price' => '상담 신청 단가',
            'is_vat_included' => '부가세 포함 여부',
            'is_free_event' => '무료 이벤트 여부',
            'is_event_period_unlimited' => '이벤트 기간 제한 없음 여부',
            'is_publish_period_unlimited' => '게시 기간 제한 없음 여부',
            'event_start_at' => '이벤트 시작 일시',
            'event_end_at' => '이벤트 종료 일시',
            'publish_start_at' => '게시 시작 일시',
            'publish_end_at' => '게시 종료 일시',
            'event_type' => '이벤트 유형',
            'has_options' => '옵션 사용 여부',
            'ratings' => '평점 목록. 1, 2, 3, 4, 5 중 복수 선택 가능',
            'average_rating' => '평균 평점',
            'rating_staff_kindness' => '직원친절도 평점',
            'rating_surgery_satisfaction' => '수술만족도 평점',
            'rating_facility' => '병원시설 평점',
            'rating_aftercare' => '사후관리 평점',
            'rating_cost' => '비용 평점',
            'assessment' => '평가 항목',
            'has_overtreatment' => '과잉진료 여부',
            'is_waiting_time_long' => '대기시간이 길었는지 여부',
            'has_doctor_consultation' => '지정의사 상담 여부',
            'is_recommended' => '지인 추천 여부',
            'receipt' => '영수증 인증 정보',
            'receipt_status' => '영수증 상태',
            'receipt_rejection_reason' => '영수증 부적합 사유',
            'receipt_rejection_reason_text' => '영수증 부적합 기타 직접입력 사유',
            'rejection_reason' => '영수증 부적합 사유',
            'rejection_reason_label' => '영수증 부적합 사유 표시명',
            'rejection_reason_text' => '영수증 부적합 기타 직접입력 사유',
            'reason' => '사유',
            'reason_text' => '기타 사유 직접입력',
            'process_reason' => '처리 사유',
            'report_reason' => '신고 사유',
            'target_type' => '대상 타입',
            'target_id' => '대상 ID',
            'target_author_id' => '대상 작성자 ID',
            'target_status' => '대상 상태',
            'hidden_reason' => '숨김 처리 사유',
            'summary_filter' => '집계 필터',
            'operation_histories_page' => '히스토리 페이지 번호',
            'operation_histories_per_page' => '히스토리 페이지당 항목 수',
            'comments_page' => '댓글 페이지 번호',
            'comments_per_page' => '댓글 페이지당 항목 수',
            'placement' => '광고 위치',
            'ad_image_file' => '광고 이미지 파일',
            'existing_ad_image_id' => '유지할 기존 광고 이미지 ID',
            'hospital_event_id' => '병의원 이벤트 ID',
            'manager_staff_id' => '담당 직원 ID',
            'ad_reception_phone_1' => '광고 접수 전화번호 첫 번째 자리',
            'ad_reception_phone_2' => '광고 접수 전화번호 두 번째 자리',
            'ad_reception_phone_3' => '광고 접수 전화번호 세 번째 자리',
            'amount_metric' => '금액 지표',
            'amount_min' => '최소 금액',
            'amount_max' => '최대 금액',
            'quantity_metric' => '수량 지표',
            'quantity_min' => '최소 수량',
            'quantity_max' => '최대 수량',
            'metric' => '지표',
            'metric_min' => '최소 지표값',
            'metric_max' => '최대 지표값',
            'thumbnail_image' => '썸네일 이미지',
            'thumbnail_file' => '썸네일 파일',
            'existing_thumbnail_file_id' => '유지할 기존 썸네일 파일 ID',
            'external_video_url' => '외부 동영상 URL',
            'duration_seconds' => '재생 시간(초)',
            'hashtag_ids' => '해시태그 ID 목록',
            'hashtag_names' => '해시태그명 목록',
            'client_message_id' => '클라이언트 메시지 고유 ID',
            'reply_to_message_id' => '답장 대상 메시지 ID',
            'message_ids' => '메시지 ID 목록',
            'message_type' => '메시지 타입',
            'metadata' => '부가 메타데이터',
            'peer_user_id' => '상대 사용자 ID',
            'last_read_message_id' => '마지막으로 읽은 메시지 ID',
            'unread_only' => '읽지 않은 항목만 조회 여부',
            'notifications_enabled' => '알림 활성화 여부',
            'in_app' => '인앱 알림 수신 여부',
            'push' => '푸시 알림 수신 여부',
            'push_token' => '푸시 토큰',
            'device_uuid' => '기기 UUID',
            'device_name' => '기기명',
            'platform' => '플랫폼',
            'channel' => '알림 채널',
            'label' => '표시명',
        ];
    }
}
