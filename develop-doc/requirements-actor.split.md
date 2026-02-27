# Beaulab Actor 분리 요구사항 정의서 (Staff / Hospital / Beauty / User)

## 목적
- `partner` 액터를 `hospital`, `beauty` 액터로 분리하고, 각 액터가 독립 로그인/세션/권한/기능을 갖도록 요구사항을 정의한다.
- 현재 `staff-web`에 구현된 기능(로그인, 권한 기반 라우팅, 병원 목록/생성 등)을 기준선으로 삼아 전 액터 요구사항으로 확장한다.

## 표준 컬럼
- 요구사항 ID
- 요구사항명
- 기능ID
- 기능명
- 상세 설명
- 필수 데이터
- 선택 데이터

---

## 1) Staff 액터 요구사항

| 요구사항 ID | 요구사항명 | 기능ID | 기능명 | 상세 설명 | 필수 데이터 | 선택 데이터 |
|---|---|---|---|---|---|---|
| STF-REQ-001 | Staff 로그인 | STF-AUTH-001 | 로그인 처리 | Staff 전용 로그인 화면에서 아이디/비밀번호로 인증 후 토큰 저장 및 관리자 홈으로 이동한다. | nickname(identifier), password, token | next(리다이렉트 경로), traceId |
| STF-REQ-002 | Staff 세션 복구 | STF-AUTH-002 | 프로필 기반 세션 복구 | 앱 진입 시 토큰 존재 시 Staff `/profile` 조회로 세션을 복구하고 실패 시 로그아웃 처리한다. | actor=staff, token, profile, permissions | roles, scope |
| STF-REQ-003 | Staff 로그아웃 | STF-AUTH-003 | 로컬 세션/토큰 제거 | 로그아웃 시 actor별 토큰 및 세션 저장소를 삭제한다. | actor=staff | 로그아웃 사유 코드 |
| STF-REQ-004 | 권한 기반 경로 접근 | STF-AUTH-004 | Guard + Route Permission | 관리자 라우트 접근 시 권한 룰 매핑으로 접근 제어하고 미인증은 로그인, 미권한은 에러 페이지로 이동한다. | pathname, requiredPermissions, session.auth.permissions | unauthorizedRedirectPath |
| STF-REQ-005 | 권한 기반 메뉴 노출 | STF-UI-001 | Sidebar 메뉴 필터링 | 사이드바는 권한이 있는 메뉴만 노출한다(대시보드/병원관리/내프로필 등). | permissions, menu definition | role 표시 정보 |
| STF-REQ-006 | 대시보드 진입 | STF-DASH-001 | 관리자 홈 | Staff 대시보드 기본 진입 라우트를 제공한다(현재는 레이아웃/진입 골격 중심). | common.dashboard.show 권한 | 위젯 데이터 |
| STF-REQ-007 | 내 프로필 조회 | STF-PROFILE-001 | 프로필 화면 | Staff 프로필 카드(메타/정보/주소) 화면 제공. | common.profile.show 권한, profile | 마지막 로그인/상태 |
| STF-REQ-008 | 병원 목록 조회 | STF-HOSP-001 | 병원 리스트 테이블 | 병원 목록을 테이블로 조회하며 상태 배지/생성일/조회수 컬럼을 표시한다. | 병원 id, name, tel, address, status, allow_status, created_at | view_count, meta(pagination) |
| STF-REQ-009 | 병원 목록 검색/필터 | STF-HOSP-002 | 통합검색 및 다중필터 | 키워드 검색(병원명/연락처/주소), 승인상태/검수상태/등록일 범위 필터를 적용한다. | q, status[], allow_status[], start_date, end_date | 필터 프리셋 |
| STF-REQ-010 | 병원 목록 정렬/페이지네이션 | STF-HOSP-003 | 정렬 + 페이지 크기 제어 | id/name/status/allow_status/view_count/created_at 정렬, per_page(15/30/50), 페이지 이동을 지원한다. | sort, direction, per_page, page | 기본 정렬 정책 |
| STF-REQ-011 | 병원 신규 등록 | STF-HOSP-004 | 병원 생성 폼 | 병원 기본 정보, 주소, 소개, 운영시간, 오시는 길, 활성화 여부를 입력해 생성한다. | name, address(또는 검색결과), activate_now | tel, email, description, consulting_hours, direction, address_detail, latitude, longitude |
| STF-REQ-012 | 병원 이미지 업로드 | STF-HOSP-005 | 로고/갤러리 업로드 | 병원 등록 시 로고 1장, 갤러리 최대 12장을 함께 전송한다. | multipart/form-data, gallery[] | logo |
| STF-REQ-013 | 병원 생성 권한 제어 | STF-HOSP-006 | 생성 버튼/경로 권한 | `beaulab.hospital.create` 권한 사용자만 병원 등록 버튼 및 생성 화면 접근 가능. | permission=beaulab.hospital.create | 권한 안내 문구 |
| STF-REQ-014 | API 응답 표준 처리 | STF-COMMON-001 | ApiResponse 처리 규약 | success/error/traceId 기반 처리, 실패 시 사용자 알림/오류 표시를 적용한다. | success, error.code, error.message | error.details, traceId |

---

## 2) Hospital 액터 요구사항 (Partner 분리 후 신규)

| 요구사항 ID | 요구사항명 | 기능ID | 기능명 | 상세 설명 | 필수 데이터 | 선택 데이터 |
|---|---|---|---|---|---|---|
| HSP-REQ-001 | Hospital 독립 로그인 | HSP-AUTH-001 | 병원 계정 인증 | 기존 partner 로그인에서 분리된 hospital 전용 로그인 엔드포인트/화면을 제공한다. | actor=hospital, identifier, password, token | next, traceId |
| HSP-REQ-002 | Hospital 세션 저장소 분리 | HSP-AUTH-002 | actor별 저장 키 분리 | hospital 액터는 staff/beauty/user와 별도의 token/session 키를 사용한다. | tokenKey, sessionKey, actor | session expireAt |
| HSP-REQ-003 | Hospital 프로필/권한 복구 | HSP-AUTH-003 | 내 병원 컨텍스트 복구 | 로그인 후 hospital 프로필과 권한을 복구해 자기 병원 범위로 기능 제한한다. | hospital_id, permissions | roles, scope=OWN_HOSPITAL |
| HSP-REQ-004 | 병원 전용 대시보드 | HSP-DASH-001 | 운영 요약 | 병원 계정은 자기 병원 통계/알림 중심 대시보드를 사용한다. | hospital_id | 기간 필터, 위젯 설정 |
| HSP-REQ-005 | 병원 정보 관리 | HSP-PROFILE-001 | 병원 기본정보 수정 | 병원명/연락처/주소/소개/운영시간/오시는 길/이미지 등을 조회·수정한다. | hospital_id, name, address | tel, email, description, media |
| HSP-REQ-006 | 병원 검수/상태 조회 | HSP-OPS-001 | 승인/검수 상태 확인 | 자기 병원의 승인상태(ACTIVE/SUSPENDED/WITHDRAWN) 및 검수 상태를 조회한다. | hospital_id, status, allow_status | 변경 이력, 사유 |
| HSP-REQ-007 | 병원 소속 사용자 관리(선택) | HSP-OPS-002 | 병원 관리자 계정 관리 | 병원 내부 운영자 계정을 생성/권한 부여하는 기능을 옵션으로 둔다. | owner account, permission set | 부서/직책 |

---

## 3) Beauty 액터 요구사항 (Partner 분리 후 신규)

| 요구사항 ID | 요구사항명 | 기능ID | 기능명 | 상세 설명 | 필수 데이터 | 선택 데이터 |
|---|---|---|---|---|---|---|
| BTW-REQ-001 | Beauty 독립 로그인 | BTW-AUTH-001 | 뷰티 계정 인증 | partner에서 분리된 beauty 전용 로그인 엔드포인트/화면을 제공한다. | actor=beauty, identifier, password, token | next, traceId |
| BTW-REQ-002 | Beauty 세션 저장소 분리 | BTW-AUTH-002 | actor별 저장 키 분리 | beauty 액터 세션을 hospital/staff/user와 독립 저장한다. | tokenKey, sessionKey, actor | expireAt |
| BTW-REQ-003 | Beauty 프로필/권한 복구 | BTW-AUTH-003 | 내 샵 컨텍스트 복구 | 로그인 후 beauty 프로필과 권한을 복구하고 범위를 자기 샵으로 제한한다. | beauty_id, permissions | roles, scope=OWN_BEAUTY |
| BTW-REQ-004 | 뷰티 전용 대시보드 | BTW-DASH-001 | 운영 요약 | 예약/시술/고객/리뷰 중심의 뷰티 운영 대시보드를 제공한다. | beauty_id | 기간별 KPI |
| BTW-REQ-005 | 샵 정보 관리 | BTW-PROFILE-001 | 샵 프로필 수정 | 샵 기본정보, 소개, 운영시간, 위치, 대표이미지를 조회·수정한다. | beauty_id, name, address | tel, SNS, media |
| BTW-REQ-006 | 검수/상태 조회 | BTW-OPS-001 | 승인/검수 상태 확인 | 뷰티 파트너 계정의 승인/검수 상태를 조회한다. | beauty_id, status, allow_status | 변경 이력, 사유 |
| BTW-REQ-007 | 소속 계정 권한관리(선택) | BTW-OPS-002 | 매장 운영자 관리 | 매장 운영자 계정 생성/비활성화/권한 부여 기능을 옵션으로 둔다. | owner account, permission set | 직무/지점 |

---

## 4) User 액터 요구사항

| 요구사항 ID | 요구사항명 | 기능ID | 기능명 | 상세 설명 | 필수 데이터 | 선택 데이터 |
|---|---|---|---|---|---|---|
| USR-REQ-001 | User 로그인 | USR-AUTH-001 | 사용자 인증 | user 전용 로그인 후 토큰/세션 저장 및 사용자 영역 진입. | actor=user, identifier, password/token | next, traceId |
| USR-REQ-002 | User 세션 복구 | USR-AUTH-002 | 프로필 기반 세션 복구 | 앱 재진입 시 `/profile`로 사용자 세션을 복구한다. | user profile, token | nickname, marketing consent |
| USR-REQ-003 | User 프로필 조회/수정 | USR-PROFILE-001 | 내 정보 관리 | 사용자 기본정보 조회 및 수정 기능 제공. | user_id | avatar, birth, gender |
| USR-REQ-004 | 권한/접근 제어 | USR-AUTH-003 | 보호 라우트 제어 | 미인증 사용자 접근 차단 및 로그인 유도 처리. | token/session 존재 여부 | redirect path |
| USR-REQ-005 | 공통 응답/오류 처리 | USR-COMMON-001 | ApiResponse 규약 준수 | success/error/traceId 기반 예외 처리 및 사용자 안내 메시지 제공. | success, error.message | traceId, details |

---

## 5) 액터 분리 공통 전환 요구사항

| 요구사항 ID | 요구사항명 | 기능ID | 기능명 | 상세 설명 | 필수 데이터 | 선택 데이터 |
|---|---|---|---|---|---|---|
| COM-REQ-001 | Actor 타입 확장 | COM-TYPE-001 | 타입 분리 | `partner`를 `hospital`, `beauty`로 분리한 ActorType/Session/Profile 타입을 정의한다. | actor enum, profile schema | 하위 호환 alias |
| COM-REQ-002 | API Prefix 분리 | COM-API-001 | 엔드포인트 분리 | 각 앱이 자기 액터 prefix만 호출하도록 강제한다 (`/staff`, `/hospital`, `/beauty`, `/user`). | actor별 baseURL | API version |
| COM-REQ-003 | 저장소 키 분리 | COM-AUTH-001 | token/session 네임스페이스 | actor별 토큰/세션 충돌을 방지하도록 키를 분리한다. | key prefix + actor | 암호화 저장 |
| COM-REQ-004 | 권한 정책 일관성 | COM-AUTH-002 | permission 기반 제어 | 메뉴/화면 노출은 Permission 기준으로 처리하고 Role 직접 분기를 지양한다. | permissions[] | role tags |
| COM-REQ-005 | 마이그레이션 가이드 | COM-MIG-001 | 파트너 데이터 분할 정책 | 기존 partner 데이터에서 hospital/beauty로 분할하는 식별 기준 및 이관 절차를 문서화한다. | partner_type, entity mapping | rollback plan |
