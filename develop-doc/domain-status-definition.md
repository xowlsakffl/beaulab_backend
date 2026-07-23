# 도메인 & 상태 정의서
- 작성일: 2026-06-29
- 목적: 서비스에서 관리하는 핵심 도메인(업무 단위)과 상태값을 비개발자도 이해할 수 있게 정리
- 기준: 현재 코드(`app/Domains/*/Models`, `database/migrations`) 기준

## 1) 도메인 한눈에

| 도메인 코드 | 이름 | 무엇을 관리하나요? |
|---|---|---|
| `AccountStaff` | 뷰랩 내부 계정 | 뷰랩 운영자/관리자 로그인 계정 |
| `AccountHospital` | 병원 계정 | 병원 소속 사용자의 로그인 계정 |
| `AccountBeauty` | 뷰티 계정 | 뷰티 소속 사용자의 로그인 계정 |
| `AccountUser` | 일반 사용자 계정 | 일반 앱 사용자 로그인 계정 |
| `AccountUserAccessLog` | 일반 사용자 접속 로그 | 앱 사용자 접근/접속 기록 |
| `AccountUserBlock` | 사용자 차단 | 앱 사용자 간 차단 관계 |
| `Hospital` | 병원 | 병원 기본 정보(소개, 위치, 연락처, 노출 여부 등) |
| `HospitalEntry` | 병의원 입점신청 | 신규 입점신청 병의원/신청자 정보와 제출 파일 |
| `Beauty` | 뷰티 업체 | 뷰티 업체 기본 정보(소개, 위치, 연락처, 노출 여부 등) |
| `HospitalDoctor` | 병원 의사 | 병원 소속 의사 프로필/자격/노출 정보 |
| `BeautyExpert` | 뷰티 전문가 | 뷰티 소속 전문가 프로필/경력/노출 정보 |
| `HospitalBusinessRegistration` | 병원 사업자등록 | 병원 사업자등록 정보와 등록증 파일 |
| `BeautyBusinessRegistration` | 뷰티 사업자등록 | 뷰티 사업자등록 정보와 등록증 파일 |
| `HospitalFeature` | 병원 특징 | 병원 관리 화면에서 선택하는 특징 마스터 |
| `HospitalVideo` | 병원 동영상 | 병원이 요청하거나 Staff가 등록한 영상과 검수/노출 상태 |
| `HospitalEvent` | 병원 이벤트 | 병원 광고/이벤트 상품, 기간, 이미지, 카테고리, 의료진 연결 |
| `HospitalEventOption` | 병원 이벤트 옵션 | 이벤트 가격/혜택 옵션 항목 |
| `HospitalEventDoctorAssignment` | 이벤트 의료진 연결 | 이벤트에 연결된 의료진 목록 |
| `HospitalEventDB` | 병원 이벤트 DB | 이벤트 상담/문의 신청 DB와 확인 상태 |
| `HospitalEventRealModelDB` | 리얼모델 DB | 리얼모델 신청자 정보, 이미지, 승인/반려 상태 |
| `Talk` | 토크 게시글 | 일반 사용자가 작성한 병원 토크 게시글, 이미지, 투표, 통계 |
| `TalkComment` | 토크 댓글 | 토크 게시글의 댓글/대댓글과 멘션 |
| `TalkCommentMention` | 토크 댓글 멘션 | 토크 댓글에서 언급한 사용자 정보 |
| `TalkPoll` | 토크 투표 | 토크 게시글에 붙는 투표 |
| `TalkPollOption` | 토크 투표 선택지 | 투표 항목 |
| `TalkPollVote` | 토크 투표 응답 | 사용자별 투표 응답 |
| `TalkSave` | 토크 저장 | 사용자별 토크 저장/스크랩 |
| `HospitalReview` | 병의원 후기 | 성형후기/시술후기 게시글, 병원/의료진/카테고리/전후 이미지/평점/비용 |
| `HospitalReviewComment` | 병의원 후기 댓글 | 후기 게시글의 댓글/대댓글과 멘션 |
| `HospitalReviewCommentMention` | 병의원 후기 댓글 멘션 | 후기 댓글에서 언급한 사용자 정보 |
| `HospitalEvaluation` | 병의원 평가 | 병의원 평가, 별점 5개 항목, 평가 선택 항목, 영수증 인증 |
| `Chat` | 채팅방 | 앱 사용자 간 1:1 채팅방 |
| `ChatMessage` | 채팅 메시지 | 텍스트/이미지/파일 메시지와 신고 대상 |
| `ChatParticipant` | 채팅 참여자 | 채팅방별 사용자 참여/읽음/알림 상태 |
| `ContentReport` | 콘텐츠 신고 로그 | 사용자가 신고한 게시글/댓글/평가의 신고 건별 기록 |
| `ContentReportItem` | 콘텐츠 신고 항목 | 한 신고 안에 포함된 개별 신고 대상 스냅샷 |
| `ContentReportState` | 콘텐츠 신고 상태 | 신고 대상별 현재 신고 상태, 신고 수, 경고/무시 처리 상태 |
| `OperationHistory` | 운영 히스토리 | 관리자 화면에 표시할 처리 이력과 변경 상세 |
| `OperationHistoryChange` | 운영 히스토리 변경 상세 | 처리 이력의 필드별 변경 전/후 값 |
| `Notice` | 공지사항 | 관리자 공지 콘텐츠(노출/게시기간/관리자 메인 팝업/조회수) |
| `Faq` | FAQ | 관리자 FAQ 콘텐츠(카테고리/채널/조회수) |
| `Category` | 카테고리 | 병원 의료/평가/토크/뷰티/FAQ 카테고리 트리 |
| `CategoryUsage` | 카테고리 사용처 | 화면/기능별 카테고리 노출 범위 |
| `Hashtag` | 해시태그 | 운영자가 관리하는 해시태그 마스터 |
| `AdminNote` | 관리자 메모 | Staff/Hospital/Beauty가 남기는 대상별 운영 메모 |
| `NotificationInbox` | 알림함 | 사용자별 알림 수신함 |
| `NotificationDelivery` | 알림 발송 로그 | 채널/제공자별 알림 발송 상태 |
| `NotificationDevice` | 알림 디바이스 | 사용자 디바이스 토큰과 플랫폼 |
| `NotificationPreference` | 알림 설정 | 사용자별 알림 이벤트 설정 |
| `Media` | 공통 미디어 | 이미지/영상 파일 메타데이터(파일 경로, 크기, 정렬, 대표 여부) |

## 2) 도메인별 상태 정의

### 2.1 `AccountStaff` (뷰랩 내부 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |

기본값:
- `status`: `STATUS_ACTIVE` (활성)

### 2.2 `AccountHospital` (병원 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |

기본값:
- `status`: `STATUS_SUSPENDED` (정지)

관계 기준:
- `AccountHospital`은 `Hospital`과 1:1이다.
- `account_hospitals.hospital_id`는 unique이며, 병원 하나에 병원 계정은 하나만 존재한다.
- Staff 병원 상세 API에서 병원 계정은 `account_hospital` 단일 객체로 내려간다.

### 2.3 `AccountBeauty` (뷰티 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |

기본값:
- `status`: `STATUS_SUSPENDED` (정지)

관계 기준:
- `AccountBeauty`는 `Beauty`와 1:1이다.
- `account_beauties.beauty_id`는 unique이며, 뷰티 업체 하나에 뷰티 계정은 하나만 존재한다.
- Staff 뷰티 상세 API에서 뷰티 계정은 `account_beauty` 단일 객체로 내려간다.

### 2.4 `AccountUser` (일반 사용자 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |
| `STATUS_WITHDRAWN` | `WITHDRAWN` | 탈퇴 | 탈퇴/비활성화된 사용자 계정 |

기본값:
- `status`: `STATUS_ACTIVE` (활성)
- `warning_count`: `0`
- `blocked_at`: `null`

신고 경고 규칙:
- 신고게시물 관리에서 `경고` 처리하면 작성자의 `warning_count`가 1 증가한다.
- `warning_count`가 10 이상이 되면 `status`는 `STATUS_BLOCKED`, `blocked_at`은 처리 시각으로 변경된다.
- 이미 경고 처리된 신고 건을 `무시`로 바꾸면 `warning_count`가 1 감소한다.
- 경고 취소 결과 `warning_count`가 10 미만이 되면 차단 상태를 `STATUS_ACTIVE`로 되돌린다.

### 2.5 `Hospital` (병원)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명   | 의미 |
|---|---|-------|---|
| `ALLOW_PENDING` | `PENDING` | 신청 | 검수 신청 접수 |
| `ALLOW_REVIEWING` | `REVIEWING` | 검수 | 운영자가 검수 진행 중 |
| `ALLOW_APPROVED` | `APPROVED` | 승인 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 반려 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 운영중 | 정상 운영/노출 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 운영정지 | 운영 일시 중단 |
| `STATUS_WITHDRAWN` | `WITHDRAWN` | 탈퇴/종료 | 운영 종료 상태 |

기본값:
- `allow_status`: `ALLOW_PENDING` (신청)
- `status`: `STATUS_SUSPENDED` (운영정지)

### 2.6 `Beauty` (뷰티 업체)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 신청 | 검수 신청 접수 |
| `ALLOW_APPROVED` | `APPROVED` | 승인 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 반려 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 운영중 | 정상 운영/노출 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 운영정지 | 운영 일시 중단 |
| `STATUS_WITHDRAWN` | `WITHDRAWN` | 탈퇴/종료 | 운영 종료 상태 |

기본값:
- `allow_status`: `ALLOW_PENDING` (신청)
- `status`: `STATUS_SUSPENDED` (운영정지)

현재 `Beauty` 모델은 병원/의료진/이벤트와 달리 `REVIEWING` 단계를 갖지 않는다.

### 2.5.1 `HospitalEntry` (병의원 입점신청)

#### 승인 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 입점신청 | 입점 신청 접수 후 승인/반려 대기 |
| `ALLOW_APPROVED` | `APPROVED` | 입점승인 | 운영자가 입점 신청을 승인 |
| `ALLOW_REJECTED` | `REJECTED` | 입점반려 | 운영자가 입점 신청을 반려 |

주요 필드:

- 병의원 정보: `hospital_name`, `hospital_phone`, `address`, `address_detail`, `business_number`, `ceo_name`, `license_number`
- 신청자 정보: `applicant_name`, `applicant_position`, `applicant_phone`, `applicant_email`
- 제출 파일은 공통 `Media` 테이블의 polymorphic relation으로 관리한다.
  - 사업자등록증: `hospital_entry_business_registration_file`
  - 면허증: `hospital_entry_license_file`

현재 API 범위:

- Staff API에서 목록/상세 조회를 제공한다.
- Staff API에서 summary 조회와 승인상태 변경을 제공한다.
- 승인상태 변경은 `operation_histories`와 `operation_history_changes`에 기록한다.

### 2.7 `HospitalDoctor` (병원 의사)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 신청 | 프로필/서류 검수 신청 |
| `ALLOW_REVIEWING` | `REVIEWING` | 검수 | 운영자가 검수 진행 중 |
| `ALLOW_APPROVED` | `APPROVED` | 승인 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 반려 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 노출/활동 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 노출/활동 비활성 |

기본값:
- `allow_status`: `ALLOW_PENDING` (신청)
- `status`: `STATUS_SUSPENDED` (정지)

### 2.8 `BeautyExpert` (뷰티 전문가)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 신청 | 프로필/서류 검수 신청 |
| `ALLOW_APPROVED` | `APPROVED` | 승인 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 반려 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 노출/활동 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 노출/활동 비활성 |

기본값:
- `allow_status`: `ALLOW_PENDING` (신청)
- `status`: `STATUS_SUSPENDED` (정지)

현재 `BeautyExpert` 모델은 병원 의료진과 달리 `REVIEWING` 단계를 갖지 않는다.

### 2.9 `HospitalBusinessRegistration` (병원 사업자등록)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 유효 | 현재 유효한 등록증 |
| `STATUS_EXPIRED` | `EXPIRED` | 만료 | 유효기간 만료 |
| `STATUS_REVOKED` | `REVOKED` | 취소/말소 | 등록이 취소되거나 말소됨 |

기본값:
- `status`: `STATUS_ACTIVE` (유효)

### 2.10 `BeautyBusinessRegistration` (뷰티 사업자등록)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 유효 | 현재 유효한 등록증 |
| `STATUS_EXPIRED` | `EXPIRED` | 만료 | 유효기간 만료 |
| `STATUS_REVOKED` | `REVOKED` | 취소/말소 | 등록이 취소되거나 말소됨 |

기본값:
- `status`: `STATUS_ACTIVE` (유효)

### 2.11 `HospitalVideo` (병원 동영상)

#### 배포 채널 (`distribution_channel`)

| 상수명 | 저장값 | 의미 |
|---|---|---|
| `DISTRIBUTION_CHANNEL_YOUTUBE_APP` | `YOUTUBE_APP` | 유튜브/앱 동시 배포 |
| `DISTRIBUTION_CHANNEL_APP` | `APP` | 앱 배포 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 노출 | 서비스 노출 가능 |
| `STATUS_INACTIVE` | `INACTIVE` | 미노출 | 서비스 비노출 |

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_SUBMITTED` | `SUBMITTED` | 제출 | 동영상 등록/요청 직후 상태 |
| `ALLOW_IN_REVIEW` | `IN_REVIEW` | 검토중 | 운영팀 검토 진행 상태 |
| `ALLOW_APPROVED` | `APPROVED` | 승인 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 반려 | 검수 반려 |
| `ALLOW_EXCLUDED` | `EXCLUDED` | 제외 | 운영 대상에서 제외 |
| `ALLOW_PARTNER_CANCELED` | `PARTNER_CANCELED` | 파트너 취소 | 병원 계정이 요청을 취소 |

기본값:
- `distribution_channel`: `DISTRIBUTION_CHANNEL_YOUTUBE_APP`
- `status`: `STATUS_INACTIVE` (미노출)
- `allow_status`: `ALLOW_SUBMITTED` (제출)
- `view_count`: `0`
- `like_count`: `0`
- `is_usage_consented`: `false`

업무 규칙(코드 기준):
- Hospital Actor API는 현재 동영상 요청 생성과 파트너 취소 라우트만 제공한다.
- Staff API는 동영상 목록/상세/생성/수정/삭제와 원본 파일 다운로드 라우트를 제공한다.

### 2.12 `Media` (공통 미디어)

- 별도 상태 상수(`STATUS_*`)는 없음
- 대신 다음 속성으로 관리
- `is_primary`: 대표 이미지/파일 여부
- `sort_order`: 노출 순서
- `deleted_at`: 삭제 여부(소프트 삭제)

### 2.13 `Notice` (공지사항)

`Notice`는 운영 상태(`status`)를 기준으로 관리한다.

#### 채널 (`channel`)

| 상수명 | 저장값 | 의미 |
|---|---|---|
| `CHANNEL_ALL` | `ALL` | 전체 대상 |
| `CHANNEL_APP_WEB` | `APP_WEB` | 앱/웹 대상 |
| `CHANNEL_HOSPITAL` | `HOSPITAL` | 병원 대상 |
| `CHANNEL_BEAUTY` | `BEAUTY` | 뷰티 대상 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 게시 가능 상태 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 게시 불가 상태 |

#### 주요 필드

- `status`: 공지 상태
- `is_pinned`: 상단 고정 여부
- `is_publish_period_unlimited`: 게시기간 무제한 여부
- `publish_start_at`, `publish_end_at`: 게시 기간
- `is_important`: 관리자 메인 팝업 여부
- `view_count`: 조회수

기본값:

- `channel`: `CHANNEL_ALL`
- `status`: `STATUS_ACTIVE`
- `is_pinned`: `false`
- `is_publish_period_unlimited`: `true`
- `is_important`: `false`
- `view_count`: `0`

### 2.14 `Faq` (자주 묻는 질문)

#### 채널 (`channel`)

| 상수명 | 저장값 | 의미 |
|---|---|---|
| `CHANNEL_ALL` | `ALL` | 전체 대상 |
| `CHANNEL_APP_WEB` | `APP_WEB` | 앱/웹 대상 |
| `CHANNEL_HOSPITAL` | `HOSPITAL` | 병원 대상 |
| `CHANNEL_BEAUTY` | `BEAUTY` | 뷰티 대상 |
| `CHANNEL_INTERNAL` | `INTERNAL` | 내부용 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 노출 가능한 상태 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 비노출 상태 |

#### 주요 필드

- `category_id`: 공통 카테고리(`Category.domain=FAQ`) 연결
- `channel`: FAQ 채널
- `question`: 질문
- `content`: 에디터 HTML 답변 본문
- `status`: FAQ 상태
- `sort_order`: 노출 순서
- `view_count`: 조회수

기본값:

- `channel`: `CHANNEL_ALL`
- `status`: `STATUS_ACTIVE`
- `sort_order`: `0`
- `view_count`: `0`

### 2.15 `Talk` / `TalkComment` (토크 게시글/댓글)

#### 노출 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 노출 | 운영 화면과 서비스에서 노출 가능 |
| `STATUS_INACTIVE` | `INACTIVE` | 미노출 | 운영자가 숨김 처리 |

업무 규칙:

- 토크 게시글과 댓글은 `status`로 실제 노출 여부를 관리한다.
- 신고 상태는 `Talk`/`TalkComment`의 게시상태 컬럼이 아니라 `ContentReportState.report_status`로 분리 관리한다.
- 신고상태가 `AUTO_BLOCKED` 또는 `ADMIN_HIDDEN`이면 일반 게시물관리 화면에서 노출/미노출 변경이 잠긴다.
- 댓글은 최상위 댓글에만 대댓글을 달 수 있다.
- 멘션은 요청에서 `mention_text`를 직접 받지 않고, 대상 사용자 id 기준으로 닉네임을 저장한다.

### 2.16 `HospitalReview` / `HospitalReviewComment` (병의원 후기/댓글)

#### 카테고리 도메인

| 상수명 | Category domain | 의미 |
|---|---|---|
| `CATEGORY_DOMAIN_SURGERY` | `HOSPITAL_REVIEW_SURGERY` | 성형후기 |
| `CATEGORY_DOMAIN_TREATMENT` | `HOSPITAL_REVIEW_TREATMENT` | 시술후기 |

#### 노출 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 노출 | 운영 화면과 서비스에서 노출 가능 |
| `STATUS_INACTIVE` | `INACTIVE` | 미노출 | 운영자가 숨김 처리 |

업무 규칙:

- 후기는 카테고리를 여러 개 가질 수 있고 최대 10개까지 허용한다.
- 성형후기/시술후기는 서로 다른 카테고리 도메인을 사용한다.
- 댓글 목록의 카테고리 기준은 부모 후기의 카테고리다.
- 댓글은 최상위 댓글에만 대댓글을 달 수 있다.
- 멘션은 대상 사용자 id 기준으로 닉네임을 저장한다.
- 신고상태가 `AUTO_BLOCKED` 또는 `ADMIN_HIDDEN`이면 일반 게시물관리 화면에서 노출/미노출 변경이 잠긴다.

### 2.17 `HospitalEvaluation` (병의원 평가)

#### 카테고리 도메인

| 상수명 | Category domain | 의미 |
|---|---|---|
| `CATEGORY_DOMAIN_SURGERY` | `HOSPITAL_EVALUATION_SURGERY` | 성형 평가 |
| `CATEGORY_DOMAIN_TREATMENT` | `HOSPITAL_EVALUATION_TREATMENT` | 시술 평가 |
| `CATEGORY_DOMAIN_CONSULTATION` | `HOSPITAL_EVALUATION_CONSULTATION` | 상담 평가 |

#### 노출 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 노출 | 운영 화면과 서비스에서 노출 가능 |
| `STATUS_INACTIVE` | `INACTIVE` | 미노출 | 운영자가 숨김 처리 |

#### 게시 상태 (`post_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `POST_STATUS_NORMAL` | `POST_NORMAL` | 정상 | 병의원 평가에 남아있는 기존 게시상태 |
| `POST_STATUS_AUTO_BLIND` | `POST_AUTO_BLIND` | 자동차단 | 기존 게시상태 컬럼 기준 자동차단 |
| `POST_STATUS_USER_DELETE` | `POST_USER_DELETE` | 본인삭제 | 작성자가 삭제한 상태 |
| `POST_STATUS_ADMIN_STOP` | `POST_ADMIN_STOP` | 노출중지 | 운영자가 게시 중지한 상태 |

주의:
- `HospitalEvaluation`에는 아직 `post_status` 컬럼과 필터가 남아 있다.
- 신규 신고게시물 관리는 `ContentReportState.report_status`를 사용하므로, 평가 쪽 `post_status`는 정리 대상이다.

#### 영수증 상태 (`receipt_status`)

| 상수명 | 저장값 | 표시명 | 의미 |
|---|---|---|---|
| `RECEIPT_STATUS_NONE` | `NONE` | 없음 | 영수증 이미지 없음 |
| `RECEIPT_STATUS_UPLOADED` | `UPLOADED` | 영수증 | 사용자가 영수증 이미지를 업로드함 |
| `RECEIPT_STATUS_VERIFIED` | `VERIFIED` | 영수증 인증 | 운영자가 적합 처리함 |
| `RECEIPT_STATUS_REJECTED` | `REJECTED` | 영수증 부적합 | 운영자가 부적합 처리함 |

#### 영수증 부적합 사유

| 상수명 | 저장값 | 표시명 |
|---|---|---|
| `RECEIPT_REJECTION_REASON_IMAGE_MISMATCH` | `IMAGE_MISMATCH` | 영수증 이미지 불일치 |
| `RECEIPT_REJECTION_REASON_BUSINESS_NAME_MISMATCH` | `BUSINESS_NAME_MISMATCH` | 상호 불일치 |
| `RECEIPT_REJECTION_REASON_BUSINESS_NUMBER_MISMATCH` | `BUSINESS_NUMBER_MISMATCH` | 사업자번호 불일치 |
| `RECEIPT_REJECTION_REASON_TRANSACTION_DATE_MISMATCH` | `TRANSACTION_DATE_MISMATCH` | 거래일시 불일치 |
| `RECEIPT_REJECTION_REASON_SURGERY_COST_MISMATCH` | `SURGERY_COST_MISMATCH` | 수술금액 불일치 |
| `RECEIPT_REJECTION_REASON_OTHER` | `OTHER` | 기타 |

평점:

- 직원친절도, 수술만족도, 병원시설, 사후관리, 비용 5개 항목을 각각 1~5점으로 저장한다.
- 각 평가의 `average_rating`은 5개 항목의 산술 평균을 소수점 1자리로 저장한다.
- 병원별 집계는 `hospitals.evaluation_count`, `hospitals.evaluation_average_rating`에 저장한다.
- 병원 집계에는 `status = ACTIVE`이고 `post_status = POST_NORMAL`인 평가만 포함한다.
- 평가 저장/삭제/복구 및 노출상태 변경 시 병원 집계를 즉시 갱신한다.
- 매일 03:30 `hospital-evaluations:refresh-hospital-ratings` 스케줄러가 평가별 `average_rating`과 병원 집계를 보정한다.

평가 선택 항목:

- 과잉진료: 있음/없음
- 대기시간: 길었음/짧았음
- 지정의사: 상담함/상담안함
- 지인추천: 추천/비추천

### 2.18 `ContentReport` / `ContentReportState` (콘텐츠 신고)

#### 신고 사유 (`ContentReport.reason`)

| 상수명 | 저장값 | 표시명 |
|---|---|---|
| `REASON_ABUSE` | `ABUSE` | 비방/욕설 |
| `REASON_SPAM` | `SPAM` | 게시물/댓글 도배 |
| `REASON_ILLEGAL_AD` | `ILLEGAL_AD` | 불법광고/홍보 |
| `REASON_PRIVACY_COPYRIGHT` | `PRIVACY_COPYRIGHT` | 개인정보/저작권 침해 |
| `REASON_OTHER` | `OTHER` | 기타 |

#### 신고 처리 상태 (`ContentReportState.report_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_NONE` | `NONE` | 없음 | 신고 접수 전 기본값 |
| `STATUS_REPORTED` | `REPORTED` | 신고접수 | 1건 이상 신고 접수 |
| `STATUS_AUTO_BLOCKED` | `AUTO_BLOCKED` | 자동차단 | 기준 시간 내 신고 누적으로 자동 미노출 |
| `STATUS_ADMIN_HIDDEN` | `ADMIN_HIDDEN` | 노출중지 | 관리자가 신고게시물 관리에서 노출중지 처리 |
| `STATUS_NORMAL_VISIBLE` | `NORMAL_VISIBLE` | 정상노출 | 관리자가 신고건을 정상노출 처리 |
| `STATUS_REEXPOSED` | `REEXPOSED` | 재노출 | 정상노출 처리 3회차부터 자동 전이 잠금 |
| `STATUS_VALID` | `VALID` | 적합 | 채팅 메시지 신고를 적합 처리 |
| `STATUS_INVALID` | `INVALID` | 부적합 | 채팅 메시지 신고를 부적합 처리 |

#### 경고 처리 상태 (`ContentReportState.warning_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `WARNING_STATUS_NONE` | `NONE` | 미처리 | 경고/무시 처리 전 |
| `WARNING_STATUS_WARNED` | `WARNED` | 경고 | 해당 신고 대상 작성자에게 경고 반영 |
| `WARNING_STATUS_IGNORED` | `IGNORED` | 무시 | 해당 신고 대상은 경고하지 않음 |

업무 규칙:

- 신고 대상은 `talk`, `talk_comment`, `hospital_review`, `hospital_review_comment`, `hospital_evaluation`, `chat_message`이다.
- 신고 로그는 건별로 `content_reports`에 저장하고, 대상별 현재 상태는 `content_report_states`에 1건만 유지한다.
- 한 신고 안에 포함된 개별 대상 스냅샷은 `content_report_items`에 저장한다.
- 1시간 내 신고 10건 이상이면 `AUTO_BLOCKED`로 변경하고 대상 콘텐츠 `status`를 `INACTIVE`로 변경한다.
- 관리자가 `NORMAL_VISIBLE`로 처리하면 대상 콘텐츠 `status`는 `ACTIVE`가 되고 `recent_hour_report_count`는 0으로 초기화된다.
- `NORMAL_VISIBLE` 처리 횟수(`normal_visible_count`)가 3회차가 되면 상태는 `REEXPOSED`로 저장된다.
- 동영상 신고만 `NORMAL_VISIBLE`, `REEXPOSED` 상태에서 `normal_visible_at` 이후 72시간 이내 추가 신고가 들어오면 상태는 유지하고 신고 수만 갱신한다.
- 동영상 신고만 `NORMAL_VISIBLE`, `REEXPOSED` 상태에서 `normal_visible_at` 이후 72시간이 지난 뒤 추가 신고가 들어오면 `REPORTED`로 전환한다. 이 전환은 자동차단 판단보다 먼저 처리한다.
- 비동영상 신고는 기존 공통 정책대로 `REEXPOSED` 이후 자동 신고접수/자동차단 상태 변경 대상에서 제외한다.
- 경고/무시는 일반 게시물/댓글/후기/평가는 `ADMIN_HIDDEN` 상태에서만 처리할 수 있고, 채팅 메시지는 `INVALID` 상태에서만 처리할 수 있다.
- 신고 상태 변경과 경고/무시 변경은 대상 콘텐츠의 operation history에 기록한다.
- operation history는 `operation_histories` 부모 이력과 `operation_history_changes` 변경 상세로 분리해 저장한다.

### 2.19 추가 도메인 상태 요약

아래 도메인은 현재 코드에 모델과 상수가 있으며, 상세 업무 흐름은 각 도메인 Action/Query와 개별 설계 문서를 기준으로 한다.

- `Category`: `domain`은 `HOSPITAL_MEDICAL`, `HOSPITAL_EVALUATION`, `TALK`, `BEAUTY`, `FAQ`를 사용하고, 병원 의료 카테고리의 `group_code`는 `SURGERY`/`TREATMENT`, `status`는 `ACTIVE`/`INACTIVE`를 사용한다.
- `CategoryUsage`: `usage`는 `HOSPITAL_DOCTOR_SUBJECT`, `HOSPITAL_REVIEW_SURGERY`, `HOSPITAL_REVIEW_TREATMENT`, `HOSPITAL_EVENT_SURGERY`, `HOSPITAL_EVENT_TREATMENT`, `HOSPITAL_VIDEO_CATEGORY`, `HOSPITAL_EVENT_AD_SURGERY`, `HOSPITAL_EVENT_AD_TREATMENT`를 사용하고, `status`는 `ACTIVE`/`INACTIVE`를 사용한다.
- `Hashtag`: `status`는 `ACTIVE`/`INACTIVE`를 사용하며, 이름은 최대 20자와 한글/영문/숫자/언더스코어 규칙을 따른다.
- `HospitalFeature`: `status`는 `ACTIVE`/`INACTIVE`를 사용한다.
- `HospitalEvent`: `type`은 `TEXT`/`IMAGE`, `status`는 `ACTIVE`/`INACTIVE`, `allow_status`는 `PENDING`/`REVIEWING`/`APPROVED`/`REJECTED`를 사용한다. 화면 표기는 `신청`/`검수`/`승인`/`반려`다.
- `HospitalEventDB`: `status`는 `NEW`/`CONFIRMED`/`DUPLICATE`, `allow_status`는 `UNVERIFIED_REPORTED`/`UNVERIFIED_CONFIRMED`/`NORMAL_CONFIRMED`를 사용한다.
- `HospitalEventRealModelDB`: `status`는 `RECEIVED`/`APPROVED`/`REJECTED`를 사용한다.
- `Chat`: `status`는 `ACTIVE`/`SUSPENDED`/`CLOSED`를 사용한다.
- `ChatMessage`: `type`은 `TEXT`/`IMAGE`/`FILE`을 사용하며, 메시지 첨부 컬렉션은 `attachments`를 사용한다.
- `NotificationDevice`: `platform`은 `IOS`/`ANDROID`/`WEB`을 사용한다.
- `NotificationDelivery`: `channel`은 `IN_APP`/`PUSH`/`EMAIL`/`WEB`, `status`는 `PENDING`/`SENT`/`FAILED`, `provider`는 `REVERB`/`FCM`/`APNS`/`MIXED`를 사용한다.
- `NotificationInbox`: 현재 사용자 알림 수신함은 `recipient_type=USER`, `actor_type=USER`, 채팅 메시지 이벤트 `chat.message.created`, 대상 `chat`을 상수로 둔다.

## 3) 상태 흐름 예시 (비개발자 관점)

### 3.1 병원 검수 흐름

- `ALLOW_PENDING`(신청) -> `ALLOW_REVIEWING`(검수) -> `ALLOW_APPROVED`(승인) 또는 `ALLOW_REJECTED`(반려)

### 3.1.1 병의원 입점신청 흐름

- `ALLOW_PENDING`(입점신청) -> `ALLOW_APPROVED`(입점승인) 또는 `ALLOW_REJECTED`(입점반려)

### 3.2 뷰티 검수 흐름

- `ALLOW_PENDING`(신청) -> `ALLOW_APPROVED`(승인) 또는 `ALLOW_REJECTED`(반려)

### 3.3 병원 의사 검수 흐름

- `ALLOW_PENDING`(신청) -> `ALLOW_REVIEWING`(검수) -> `ALLOW_APPROVED`(승인) 또는 `ALLOW_REJECTED`(반려)

### 3.4 뷰티 전문가 검수 흐름

- `ALLOW_PENDING`(신청) -> `ALLOW_APPROVED`(승인) 또는 `ALLOW_REJECTED`(반려)

### 3.5 영상요청 검토 흐름

- `ALLOW_SUBMITTED`(제출) -> `ALLOW_IN_REVIEW`(검토중) -> `ALLOW_APPROVED`(승인) 또는 `ALLOW_REJECTED`(반려)
- 제출 단계에서는 파트너가 `ALLOW_PARTNER_CANCELED`(파트너 취소)로 종료 가능
- 운영 제외가 필요하면 `ALLOW_EXCLUDED`를 사용한다.

### 3.6 공지/FAQ/콘텐츠 노출 흐름

- `STATUS_ACTIVE`(노출/활성) -> `STATUS_INACTIVE`(미노출/비활성)
- 게시 시작/종료 시각은 기간 제어용 필드이며 별도 노출 상태 enum으로 관리하지 않음
- 신고게시물 관리에서 자동차단/노출중지가 되면 대상 콘텐츠의 `status`가 `INACTIVE`로 바뀐다.
- 신고게시물 관리에서 정상노출 처리하면 대상 콘텐츠의 `status`가 `ACTIVE`로 바뀐다.

## 4) 참고 파일

- `app/Domains/*/Models/*.php`
- `app/Domains/Common/ContentReport/*`
- `database/migrations/0001_01_01_0000*_create_*.php`
- `database/migrations/2026_02_*_create_*.php`
- `database/migrations/2026_03_12_120000_create_notices_table.php`
- `database/migrations/2026_05_15_090000_create_content_reports_table.php`
- `database/migrations/2026_05_15_090100_create_content_report_states_table.php`
