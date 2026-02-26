# Authorization & Navigation 설계 (Staff / Partner / User)

이 문서는 **현재 코드 기준**의 권한 모델(Role/Permission/Guard)과 운영 규칙을 정리합니다.
권한 단일 소스는 아래 두 파일입니다.

- `app/Common/Authorization/AccessPermissions.php`
- `app/Common/Authorization/AccessRoles.php`

---

## 1) Guard 구분

- `staff` : 내부 직원
- `partner` : 병원/뷰티/대행사 파트너
- `user` : 일반 앱 사용자

Permission은 guard별로 생성/관리하며, Seeder에서 guard를 기준으로 동기화합니다.

---

## 2) Role 목록

### 2.1 Staff (`guard: staff`)
- `beaulab.super_admin`
- `beaulab.admin`
- `beaulab.staff`
- `beaulab.dev`

### 2.2 Partner (`guard: partner`)
- Hospital: `hospital.owner`, `hospital.manager`, `hospital.staff`
- Beauty: `beauty.owner`, `beauty.manager`, `beauty.staff`
- Agency: `agency.owner`, `agency.staff`

### 2.3 User (`guard: user`)
- 현재 role 기반 매핑은 비워둔 상태(필요 시 확장)

---

## 3) Permission 목록 (현재 코드 반영)

## 3.1 Common
- `common.access`
- `common.dashboard.show`
- `common.profile.show`
- `common.profile.update`

### 3.2 Beaulab(Staff 전용)
- Hospital: `beaulab.hospital.show|create|update|delete`
- Beauty: `beaulab.beauty.show|create|update|delete`
- Agency: `beaulab.agency.show|create|update|delete`
- User: `beaulab.user.show|update|delete`
- Doctor: `beaulab.doctor.show|create|update|delete`
- Expert: `beaulab.expert.show|create|update|delete`

### 3.3 Partner 전용
- Hospital: `hospital.profile.show|update|delete`, `hospital.members.manage`
- Beauty: `beauty.profile.show|update|delete`, `beauty.members.manage`
- Agency: `agency.profile.show|update|delete`, `agency.members.manage`

### 3.4 User 전용
- `user.profile.show`
- `user.profile.update`

---

## 4) Role → Permission 매핑 요약

- `beaulab.super_admin`: staff guard의 모든 permission
- `beaulab.admin`: common + beaulab 전체
- `beaulab.staff`, `beaulab.dev`: common + 조회 중심 권한
  - `beaulab.*.show` (hospital/beauty/agency/user/doctor/expert)

- Partner owner 계열: common + 도메인 전체 권한
- Partner manager 계열: 조회/수정/멤버관리 중심
- Partner staff 계열: 조회 중심

---

## 5) Seeder 동기화 원칙

권한/역할 변경 후 아래 시더로 동기화합니다.

```bash
php artisan db:seed --class=AuthorizationSeeder
```

Seeder 동작:
1. PermissionRegistrar 캐시 초기화
2. guard별 permission 생성
3. guard별 role 생성
4. role-permission sync
5. 캐시 재초기화

---

## 6) API 레벨 권한 체크 규칙

- 보호 API는 인증 미들웨어를 통과해야 함
- 기능 접근은 `permission:*` 미들웨어 + Policy로 제어
- 데이터 범위(소속 병원/뷰티 등)는 Query/Policy에서 강제
- 메뉴 노출은 UX 보조이며, 최종 차단은 서버 권한 체크로 보장

---

## 7) 운영 시 주의사항

- Permission 문자열은 코드/문서 1:1로 유지
- guard 혼합 금지(같은 role에 다른 guard permission 연결 금지)
- 권한 변경은 감사로그 대상(부여/회수/동기화)

---

작성 기준: 2026-02-26 (Doctor/Expert/User 권한 포함)
