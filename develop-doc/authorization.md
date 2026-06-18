# Authorization & Navigation 설계 (Staff / Hospital / Beauty / User)

이 문서는 현재 코드 기준의 권한 모델(Role/Permission/Guard)과 운영 규칙을 정리한다.  
권한 단일 소스는 아래 두 파일이다.

- `app/Common/Authorization/AccessPermissions.php`
- `app/Common/Authorization/AccessRoles.php`

## 1) Guard 구분

- `staff`: 내부 직원
- `hospital`: 병원 파트너
- `beauty`: 뷰티 파트너
- `user`: 일반 앱 사용자

Permission은 Staff/Hospital/Beauty guard별로 생성/관리하며 Seeder에서 동기화한다.
User는 Spatie role/permission을 사용하지 않는다.

## 2) Role 목록

### 2.1 Staff (`guard: staff`)

- `beaulab.super_admin`
- `beaulab.admin`
- `beaulab.staff`
- `beaulab.dev`

### 2.2 Hospital (`guard: hospital`)

- `hospital.owner`
- 현재 파트너 계정 구조는 `Hospital` 1개당 `AccountHospital` 1개다.
- 병원 파트너 role은 `hospital.owner` 단일로 운영한다.
- 향후 다계정 정책이 다시 필요해지면 `manager`, `staff` 역할을 새로 정의해 확장한다.

### 2.3 Beauty (`guard: beauty`)

- `beauty.owner`
- `beauty.manager`
- `beauty.staff`
- 현재 파트너 계정 구조는 `Beauty` 1개당 `AccountBeauty` 1개다.
- Role/Permission 체계는 유지하지만, 뷰티 업체에 여러 뷰티계정을 발급하는 구조는 사용하지 않는다.

### 2.4 User (`guard: user`)

- User API는 Spatie role/permission을 사용하지 않는다.
- 사용자 기능 권한은 인증 여부, 계정 상태, 도메인 Policy/Action 규칙으로 처리한다.

## 3) Permission 목록 (현재 코드 반영)

### 3.1 Common

- `common.access`
- `common.dashboard.show`
- `common.profile.show`
- `common.profile.update`

### 3.2 Beaulab(Staff 전용)

- Hospital: `beaulab.hospital.show|create|update|delete`
- Beauty: `beaulab.beauty.show|create|update|delete`
- Agency: `beaulab.agency.show|create|update|delete` (정의만 존재, API 미연결)
- User: `beaulab.user.show|update|delete`
- Doctor: `beaulab.doctor.show|create|update|delete`
- Expert: `beaulab.expert.show|create|update|delete`
- Video: `beaulab.video.show|create|update|delete`
- Hospital Review: `beaulab.hospital_review.show|update`
- Hospital Review Comment: 별도 권한 없음 (Hospital Review 권한 `beaulab.hospital_review.show|update` 사용)
- Hospital Evaluation: `beaulab.hospital_evaluation.show|update`
- Talk: `beaulab.talk.show|update`
- Talk Comment: 별도 권한 없음 (Talk 권한 `beaulab.talk.show|update` 사용)
- Reported Content: 별도 권한 없음. 신고 대상 도메인의 show/update Policy를 그대로 사용
- Notice: `beaulab.notice.show|create|update|delete`
- FAQ: `beaulab.faq.show|create|update|delete`
- Category: `beaulab.category.manage`
- Hashtag: `beaulab.hashtag.manage`

### 3.3 Hospital 전용

- `hospital.profile.show|update|delete`
- `hospital.video.show|create|update|cancel`

### 3.4 Beauty 전용

- `beauty.profile.show|update|delete`
- `beauty.members.manage`
- `beauty.video.show|create|update|cancel`

### 3.5 User 전용

- 없음. User guard 권한/역할은 생성하지 않는다.

## 4) Role -> Permission 매핑 요약

- `beaulab.super_admin`
  - staff guard의 전체 permission
- `beaulab.admin`
  - common + beaulab 전체
- `beaulab.staff`, `beaulab.dev`
  - common + 조회 중심 권한
  - Notice/FAQ는 기본적으로 `beaulab.notice.show`, `beaulab.faq.show`만 포함

- `hospital.owner`
  - common + hospital 전체
- 과거 다계정 병원 role
  - 현재 사용하지 않음. Seeder에서 잔여 role을 제거한다.

- `beauty.owner`
  - common + beauty 전체
- `beauty.manager`
  - common + 조회/수정/멤버관리 중심
- `beauty.staff`
  - common + 조회 중심


## 5) 라우트 보호 정책

- Staff 모듈
  - `auth:sanctum`
  - `abilities:actor:staff`
  - `permission:common.access`
- Hospital 모듈
  - `auth:sanctum`
  - `abilities:actor:hospital`
- Beauty 모듈
  - `auth:sanctum`
  - `abilities:actor:beauty`

## 6) 내부도구 권한 정책

- 대상: Horizon, Telescope, Swagger 같은 웹 기반 운영 도구
- 공용 Gate: `viewTool`
- 공용 세션 가드: `tool_staff`
- 공용 추가 보호: `internal_tool.ip`

허용 기준:

1. `ACTIVE` Staff 계정
2. 역할이 `beaulab.super_admin` 또는 `beaulab.dev`
3. `INTERNAL_TOOL_ALLOWED_EMAILS`가 설정된 경우 이메일도 허용 목록 일치

즉, 내부도구는 API용 `staff` 토큰 가드와 별도로 웹 세션 가드 `tool_staff`를 사용한다.

상세 구조와 로그인 흐름은 `develop-doc/internal-tools.md`를 본다.

## 7) 운영 규칙

1. 문자열 하드코딩 대신 `AccessPermissions`, `AccessRoles` 상수 사용
2. 신규 API 추가 시 라우트 미들웨어/Policy/Seeder 동시 반영
3. 권한 변경 후 반드시 시더 동기화

```bash
php artisan db:seed --class=AuthorizationSeeder
```

작성 기준: 2026-05-18
