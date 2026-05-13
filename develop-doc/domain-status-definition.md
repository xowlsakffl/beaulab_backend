# 도메인 & 상태 정의서 (비개발자용)

- 작성일: 2026-05-13
- 목적: 서비스에서 관리하는 핵심 도메인(업무 단위)과 상태값을 비개발자도 이해할 수 있게 정리
- 기준: 현재 코드(`app/Domains/*/Models`, `database/migrations`) 기준

## 1) 도메인 한눈에

| 도메인 코드 | 이름 | 무엇을 관리하나요? |
|---|---|---|
| `AccountStaff` | 뷰랩 내부 계정 | 뷰랩 운영자/관리자 로그인 계정 |
| `AccountHospital` | 병원 계정 | 병원 소속 사용자의 로그인 계정 |
| `AccountBeauty` | 뷰티 계정 | 뷰티 소속 사용자의 로그인 계정 |
| `AccountUser` | 일반 사용자 계정 | 일반 앱 사용자 로그인 계정 |
| `Hospital` | 병원 | 병원 기본 정보(소개, 위치, 연락처, 노출 여부 등) |
| `Beauty` | 뷰티 업체 | 뷰티 업체 기본 정보(소개, 위치, 연락처, 노출 여부 등) |
| `HospitalDoctor` | 병원 의사 | 병원 소속 의사 프로필/자격/노출 정보 |
| `BeautyExpert` | 뷰티 전문가 | 뷰티 소속 전문가 프로필/경력/노출 정보 |
| `HospitalBusinessRegistration` | 병원 사업자등록 | 병원 사업자등록 정보와 등록증 파일 |
| `BeautyBusinessRegistration` | 뷰티 사업자등록 | 뷰티 사업자등록 정보와 등록증 파일 |
| `HospitalVideoRequest` | 병원 영상요청 | 병원이 등록/게시를 요청한 영상의 검수 상태 |
| `Talk` | 토크 게시글 | 일반 사용자가 작성한 병원 토크 게시글, 이미지, 투표, 통계 |
| `TalkComment` | 토크 댓글 | 토크 게시글의 댓글/대댓글과 멘션 |
| `HospitalReview` | 병의원 후기 | 성형후기/시술후기 게시글, 병원/의료진/카테고리/전후 이미지/평점/비용 |
| `HospitalReviewComment` | 병의원 후기 댓글 | 후기 게시글의 댓글/대댓글과 멘션 |
| `HospitalEvaluation` | 병의원 평가 | 병의원 평가, 별점 5개 항목, 평가 선택 항목, 영수증 인증 |
| `Notice` | 공지사항 | 관리자 공지 콘텐츠(노출/게시기간/관리자 메인 팝업/조회수) |
| `Faq` | FAQ | 관리자 FAQ 콘텐츠(카테고리/채널/조회수) |
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

### 2.3 `AccountBeauty` (뷰티 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |

기본값:
- `status`: `STATUS_SUSPENDED` (정지)

### 2.4 `AccountUser` (일반 사용자 계정)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 정상적으로 로그인/사용 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 상태 |
| `STATUS_BLOCKED` | `BLOCKED` | 차단 | 관리자 차단 상태 |

기본값:
- `status`: `STATUS_ACTIVE` (활성)

### 2.5 `Hospital` (병원)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명   | 의미 |
|---|---|-------|---|
| `ALLOW_PENDING` | `PENDING` | 검수 대기 | 검수 신청 후 결과 대기 |
| `ALLOW_APPROVED` | `APPROVED` | 검수 완료 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 검수 거절 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 운영중 | 정상 운영/노출 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 운영정지 | 운영 일시 중단 |
| `STATUS_WITHDRAWN` | `WITHDRAWN` | 탈퇴/종료 | 운영 종료 상태 |

기본값:
- `allow_status`: `ALLOW_PENDING` (검수 대기)
- `status`: `STATUS_SUSPENDED` (운영정지)

### 2.6 `Beauty` (뷰티 업체)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 검수 대기 | 검수 신청 후 결과 대기 |
| `ALLOW_APPROVED` | `APPROVED` | 검수 완료 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 검수 거절 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 운영중 | 정상 운영/노출 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 운영정지 | 운영 일시 중단 |
| `STATUS_WITHDRAWN` | `WITHDRAWN` | 탈퇴/종료 | 운영 종료 상태 |

기본값:
- `allow_status`: `ALLOW_PENDING` (검수 대기)
- `status`: `STATUS_SUSPENDED` (운영정지)

### 2.7 `HospitalDoctor` (병원 의사)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 검수 대기 | 프로필/서류 검수 대기 |
| `ALLOW_APPROVED` | `APPROVED` | 검수 완료 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 검수 거절 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 노출/활동 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 노출/활동 비활성 |

기본값:
- `allow_status`: `ALLOW_PENDING` (검수 대기)
- `status`: `STATUS_SUSPENDED` (정지)

### 2.8 `BeautyExpert` (뷰티 전문가)

#### 검수 상태 (`allow_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `ALLOW_PENDING` | `PENDING` | 검수 대기 | 프로필/서류 검수 대기 |
| `ALLOW_APPROVED` | `APPROVED` | 검수 완료 | 검수 통과 |
| `ALLOW_REJECTED` | `REJECTED` | 검수 거절 | 검수 반려 |

#### 운영 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 활성 | 노출/활동 가능 |
| `STATUS_SUSPENDED` | `SUSPENDED` | 정지 | 일시 중지 |
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 노출/활동 비활성 |

기본값:
- `allow_status`: `ALLOW_PENDING` (검수 대기)
- `status`: `STATUS_SUSPENDED` (정지)

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

### 2.11 `HospitalVideoRequest` (병원 영상요청)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `REVIEW_STATUS_APPLYING` | `APPLYING` | 신청중 | 파트너가 요청을 올린 직후 상태 |
| `REVIEW_STATUS_IN_REVIEW` | `IN_REVIEW` | 검토중 | 운영팀 검토 진행 상태 |
| `REVIEW_STATUS_APPROVED` | `APPROVED` | 검수 | 게시 가능 검수 완료 |
| `REVIEW_STATUS_REJECTED` | `REJECTED` | 반려 | 검토 결과 거절 |
| `REVIEW_STATUS_PARTNER_CANCELED` | `PARTNER_CANCELED` | 파트너 취소 | 요청자가 직접 취소 |

기본값:
- `review_status`: `REVIEW_STATUS_APPLYING` (신청중)

업무 규칙(코드 기준):
- 파트너가 `수정` 또는 `취소`할 수 있는 상태는 `REVIEW_STATUS_APPLYING`(신청중)일 때만 가능

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

### 2.14 `Talk` / `TalkComment` (토크 게시글/댓글)

#### 노출 상태 (`status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `STATUS_ACTIVE` | `ACTIVE` | 노출 | 운영 화면과 서비스에서 노출 가능 |
| `STATUS_INACTIVE` | `INACTIVE` | 미노출 | 운영자가 숨김 처리 |

#### 게시 상태 (`post_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `POST_STATUS_NORMAL` | `POST_NORMAL` | 정상 | 일반 게시 상태 |
| `POST_STATUS_AUTO_BLIND` | `POST_AUTO_BLIND` | 자동차단 | 자동 정책으로 차단된 상태 |
| `POST_STATUS_USER_DELETE` | `POST_USER_DELETE` | 본인삭제 | 작성자가 삭제한 상태 |
| `POST_STATUS_ADMIN_STOP` | `POST_ADMIN_STOP` | 노출중지 | 운영자가 게시 중지한 상태 |

업무 규칙:

- `POST_AUTO_BLIND`, `POST_USER_DELETE`, `POST_ADMIN_STOP` 상태는 노출/미노출 변경이 잠긴다.
- 댓글은 최상위 댓글에만 대댓글을 달 수 있다.
- 멘션은 요청에서 `mention_text`를 직접 받지 않고, 대상 사용자 id 기준으로 닉네임을 저장한다.

### 2.15 `HospitalReview` / `HospitalReviewComment` (병의원 후기/댓글)

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

#### 게시 상태 (`post_status`)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `POST_STATUS_NORMAL` | `POST_NORMAL` | 정상 | 일반 게시 상태 |
| `POST_STATUS_AUTO_BLIND` | `POST_AUTO_BLIND` | 자동차단 | 자동 정책으로 차단된 상태 |
| `POST_STATUS_USER_DELETE` | `POST_USER_DELETE` | 본인삭제 | 작성자가 삭제한 상태 |
| `POST_STATUS_ADMIN_STOP` | `POST_ADMIN_STOP` | 노출중지 | 운영자가 게시 중지한 상태 |

업무 규칙:

- 후기는 카테고리를 여러 개 가질 수 있고 최대 10개까지 허용한다.
- 성형후기/시술후기는 서로 다른 카테고리 도메인을 사용한다.
- 댓글 목록의 카테고리 기준은 부모 후기의 카테고리다.
- 댓글은 최상위 댓글에만 대댓글을 달 수 있다.
- 멘션은 대상 사용자 id 기준으로 닉네임을 저장한다.
- `POST_AUTO_BLIND`, `POST_USER_DELETE`, `POST_ADMIN_STOP` 상태는 노출/미노출 변경이 잠긴다.

### 2.16 `HospitalEvaluation` (병의원 평가)

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
| `POST_STATUS_NORMAL` | `POST_NORMAL` | 정상 | 일반 게시 상태 |
| `POST_STATUS_AUTO_BLIND` | `POST_AUTO_BLIND` | 자동차단 | 자동 정책으로 차단된 상태 |
| `POST_STATUS_USER_DELETE` | `POST_USER_DELETE` | 본인삭제 | 작성자가 삭제한 상태 |
| `POST_STATUS_ADMIN_STOP` | `POST_ADMIN_STOP` | 노출중지 | 운영자가 게시 중지한 상태 |

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
- 목록 평균 평점은 5개 항목의 산술 평균이다.

평가 선택 항목:

- 과잉진료: 있음/없음
- 대기시간: 길었음/짧았음
- 지정의사: 상담함/상담안함
- 지인추천: 추천/비추천

## 3) 상태 흐름 예시 (비개발자 관점)

### 3.1 병원 검수 흐름

- `ALLOW_PENDING`(검수 대기) -> `ALLOW_APPROVED`(검수 완료) 또는 `ALLOW_REJECTED`(검수 반려)

### 3.2 뷰티 검수 흐름

- `ALLOW_PENDING`(검수 대기) -> `ALLOW_APPROVED`(검수 완료) 또는 `ALLOW_REJECTED`(검수 반려)

### 3.3 병원 의사 검수 흐름

- `ALLOW_PENDING`(검수 대기) -> `ALLOW_APPROVED`(검수 완료) 또는 `ALLOW_REJECTED`(검수 반려)

### 3.4 뷰티 전문가 검수 흐름

- `ALLOW_PENDING`(검수 대기) -> `ALLOW_APPROVED`(검수 완료) 또는 `ALLOW_REJECTED`(검수 반려)

### 3.5 영상요청 검토 흐름

- `REVIEW_STATUS_APPLYING`(신청중) -> `REVIEW_STATUS_IN_REVIEW`(검토중) -> `REVIEW_STATUS_APPROVED`(검수) 또는 `REVIEW_STATUS_REJECTED`(반려)
- 신청중 단계에서는 파트너가 `REVIEW_STATUS_PARTNER_CANCELED`(파트너 취소)로 종료 가능

### 3.6 공지 노출 흐름

- `STATUS_ACTIVE`(활성) -> `STATUS_INACTIVE`(비활성)
- 게시 시작/종료 시각은 기간 제어용 필드이며 별도 노출 상태 enum으로 관리하지 않음

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

## 4) 참고 파일

- `app/Domains/*/Models/*.php`
- `database/migrations/0001_01_01_0000*_create_*.php`
- `database/migrations/2026_02_*_create_*.php`
- `database/migrations/2026_03_12_120000_create_notices_table.php`
