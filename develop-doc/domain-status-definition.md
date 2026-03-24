# 도메인 & 상태 정의서 (비개발자용)

- 작성일: 2026-03-12
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
| `Notice` | 공지사항 | 관리자 공지 콘텐츠(노출/게시기간/중요공지/조회수) |
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

`Notice`는 운영 상태(`status`)와 계산 노출 상태(`exposure_status`)를 함께 사용한다.

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
| `STATUS_INACTIVE` | `INACTIVE` | 비활성 | 숨김 상태 |

#### 노출 상태 (`exposure_status`, 계산값)

| 상수명 | 저장값 | 상태명 | 의미 |
|---|---|---|---|
| `EXPOSURE_HIDDEN` | `HIDDEN` | 숨김 | `status=INACTIVE` |
| `EXPOSURE_SCHEDULED` | `SCHEDULED` | 게시 대기 | `publish_start_at` 미래 시각 |
| `EXPOSURE_PUBLISHED` | `PUBLISHED` | 게시중 | 노출 가능 기간 내 |
| `EXPOSURE_EXPIRED` | `EXPIRED` | 게시 종료 | 게시 종료 시각 경과 |

#### 주요 필드

- `status`: 공지 상태
- `is_pinned`: 상단 고정 여부
- `pinned_order`: 상단 정렬 순서
- `is_publish_period_unlimited`: 게시기간 무제한 여부
- `publish_start_at`, `publish_end_at`: 게시 기간
- `is_important`: 중요 공지(팝업) 여부
- `view_count`: 조회수

기본값:

- `channel`: `CHANNEL_ALL`
- `status`: `STATUS_ACTIVE`
- `is_pinned`: `false`
- `pinned_order`: `0`
- `is_publish_period_unlimited`: `true`
- `is_important`: `false`
- `view_count`: `0`

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

- `EXPOSURE_HIDDEN`(숨김) -> `EXPOSURE_SCHEDULED`(게시 대기) -> `EXPOSURE_PUBLISHED`(게시중) -> `EXPOSURE_EXPIRED`(게시 종료)
- `status=INACTIVE`면 게시기간과 무관하게 `EXPOSURE_HIDDEN`으로 처리
- `publish_start_at` 미래 시각이면 `EXPOSURE_SCHEDULED`로 처리

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
