# Architecture (구조 / 흐름)

이 문서는 Beaulab 프로젝트의 **전체 구조와 요청 흐름**을 설명합니다.  
본 프로젝트는 **라라벨의 기본 흐름을 존중하면서**,  
관리자(Admin) / 앱 사용자(User) / 도메인(Domain)을 **명확히 분리**하는 것을 목표로 합니다.

---

## 1. 큰 그림

본 프로젝트는 아래 **3가지 요청 영역**으로 구성됩니다.

| 구분 | 목적 | URL Prefix | 응답 |
|---|---|---|---|
| 관리자 페이지 (Inertia) | 관리자 화면 렌더링 / 페이지 네비게이션 | `/admin/*` | HTML + Inertia |
| 관리자 API | 관리자 화면의 동적 데이터(CRUD, 테이블, 필터) | `/admin/api/*` | JSON |
| 앱 사용자 API | 모바일/외부 클라이언트 통신 | `/api/*` | JSON |

### 핵심 원칙
- 페이지 이동/폼 제출은 **Inertia 기본 흐름**을 따른다.
- 테이블/필터/모달 등 **동적 UI 데이터**는 관리자 API(`/admin/api/*`)로 분리한다.
- 앱 사용자 API는 **Sanctum 토큰 기반**으로 `/api/*`에서 처리한다.

---

## 2. 백엔드 디렉토리 구조


- `app/Modules/Admin/*`
    - 관리자 영역(세션 기반 인증, 관리자 전용 화면/요청)
- `app/Modules/User/*`
    - 앱 사용자 영역(토큰 기반 API, 앱 사용자 전용 요청)
- `app/Domains/*`
    - **업무 도메인(비즈니스 로직)**
    - 예: `Hospital`, `Review`, `Beauty`, `Reservation` 등
    - 도메인 안에는 모델/유스케이스/정책/쿼리 등 **규칙과 상태**가 위치한다
- `app/Common/*`
    - 공통: `ApiResponse`, `ErrorCode`, `CustomException`, 공통 유틸/예외 등
- `app/Modules/Admin/routes/`
    - `web.php` : 관리자 Inertia 페이지 라우트(`/admin/*`)
    - `admin.php` : 관리자 API 라우트(`/admin/api/*`)
- `app/Modules/User/routes/`
    - `api.php` : 앱 사용자 API 라우트(`/api/*`)

---

## 3. 핵심 설계 원칙(중요)

### 3.1 “모델은 무조건 도메인(Domains)”
- Eloquent Model은 `app/Domains/{Domain}/Models` 아래에만 둔다.
- `Modules/Admin`, `Modules/User`에는 모델을 만들지 않는다. (역할/표현 레이어는 얇게)

> 이유: Admin/User는 “누가 호출하느냐(Actor)”의 차이이고,  
> Hospital/Review 같은 “무엇을 다루느냐(도메인)”가 진짜 비즈니스 규칙의 주인공이기 때문.

### 3.2 컨트롤러는 얇게, 비즈니스 로직은 도메인 Action으로
컨트롤러는 다음만 담당한다.
- Request(FormRequest)로 validation/authorize
- Domain Action 호출
- DTO로 응답 변환

컨트롤러에서 금지(원칙)
- 길고 복잡한 쿼리 빌드
- 트랜잭션/상태 머신/승인 플로우
- 권한 if문 분기(Policy/FormRequest로 이동)

### 3.3 “Admin / App(사용자)” 차이는 Action과 응답 스키마로 분리
관리자와 유저가 같은 유스케이스 이름(예: 리스트 조회)을 호출해도 보통 아래가 다르다.
- 기본 필터(유저는 공개/승인만, 관리자는 전체/상태필터 가능)
- 허용 필터/정렬(운영 vs 사용자 UX)
- 응답 필드(관리자는 내부 컬럼 포함 가능)

따라서 도메인 내부에서 다음을 분리한다.
- `Actions/Admin/*` 와 `Actions/User/*` : **유스케이스 규칙(필터/권한/기본 조건)**
- `Dto/Admin/*` 와 `Dto/User/*` : **응답 스키마(필드셋)**

### 3.4 공통 쿼리/로직은 Query/Support로 흡수
Action 간 중복이 생기면 “거대한 Action”을 만들기보다 아래로 흡수한다.
- `Queries/*` : 검색/필터/정렬/페이징 같은 쿼리 빌딩 블록
- `Supports/*` : Action들이 공유하는 작은 부품(예: 이미지 처리, 슬러그 생성 등)
  특정 도메인에서 자주 쓰는 계산/변환/동기화
  평점/카운트 재계산
  주소/전화번호 포맷 정규화
  검색 조건 빌딩 일부(쿼리랑 겹치면 Query로)
  외부 시스템 연동이 “도메인 기능”으로 의미가 있을 때

---

### 메소드/클래스 명명 규칙 (Beaulab 표준)
1) Controller 메소드 (HTTP 진입점)

- 목표: 라우트가 한 눈에 보이고, “페이지 렌더 vs API”가 즉시 구분되게.

#### 1-1. Inertia 페이지 렌더
패턴: page{Verb}{Noun}For{Role}

예:
- pageHospitalIndexForStaff() (GET /admin/hospitals)
- pageHospitalCreateForStaff() (GET /admin/hospitals/create)
페이지는 “데이터 로드”를 하지 않고, 렌더만. 데이터는 별도 API에서 로드.

#### 1-2. Admin API (JSON)

패턴: api{Verb}{Noun}For{Role}

예:
- apiGetHospitalListForStaff() (GET /admin/api/hospitals)
- apiCreateHospitalForStaff() (POST /admin/api/hospitals)
- apiUpdateHospitalForStaff() (PUT /admin/api/hospitals/{id})
- apiDeleteHospitalForStaff() (DELETE /admin/api/hospitals/{id})

---

## 4. 도메인 디렉토리 템플릿(예: Hospital)

권장 기본 형태:

- `app/Domains/Hospital/Models/Hospital.php`
- `app/Domains/Hospital/Actions/Admin/ListHospitals.php`
- `app/Domains/Hospital/Actions/User/ListHospitals.php`
- `app/Domains/Hospital/Queries/HospitalQuery.php`
- `app/Domains/Hospital/Dto/Admin/HospitalListItem.php`
- `app/Domains/Hospital/Dto/User/HospitalListItem.php`
-  `app/Domains/Hospital/Policies/HospitalPolicy.php`

---

## 5. 프론트 구조(Inertia + React)

### 5.1 Inertia 페이지 경로 규칙
- Inertia는 `Inertia::render('{name}')`에서 전달된 name을 기준으로 아래 파일을 로드합니다.
- 페이지 파일 위치:
    - `resources/js/pages/**`

예)
- 서버: `Inertia::render('admin/dashboard')`
- 프론트: `resources/js/pages/admin/dashboard.tsx`

### 5.2 관리자 레이아웃 구성
- 관리자 앱 레이아웃: `resources/js/layouts/admin/app-layout.tsx`
- 관리자 인증 레이아웃: `resources/js/layouts/admin/auth-layout.tsx`
- 관리자 설정 레이아웃: `resources/js/layouts/admin/settings/layout.tsx`

---

## 6. 인증(Authentication)

### 6.1 관리자 (세션 기반)
- Guard: `admin` (session)
- 로그인: `/admin/login` (Fortify)
- 보호 페이지: `/admin/*` + `auth:admin`

### 6.2 앱 사용자 (토큰 기반)
- Guard: `sanctum` (token)
- 보호 API: `/api/*` + `auth:sanctum`
- 토큰 저장: `personal_access_tokens` 테이블

---

## 7. 라우팅/응답/예외 처리 기준

### 7.1 응답 타입 기준
- `/admin/*` : Inertia 응답(HTML/redirect/errors) 유지
- `/admin/api/*` : JSON (공통 포맷)
- `/api/*` : JSON (공통 포맷)

### 7.2 예외 처리의 기본 원칙
- API 구간(`/api/*`, `/admin/api/*`)은 `ApiResponse::error()`로 통일
- Inertia 페이지(`/admin/*`)는 기본 흐름(redirect + errors)을 깨지 않도록 JSON 에러를 강제하지 않음

자세한 내용은 [에러/예외 처리](./error-handling.md)를 참고합니다.

---

## 8. 실수 방지 체크리스트(짧게)

- [ ] 새로운 비즈니스 모델을 만들면 `app/Domains/*/Models`에 만들었나?
- [ ] 컨트롤러가 30줄을 넘어가면 Action/Query로 분리할 시점인가?
- [ ] Admin/App 응답 스키마가 다른데 같은 Resource를 공유하고 있진 않은가?
- [ ] API 에러는 `/api/*`, `/admin/api/*`에서 `ApiResponse` 포맷을 따르는가?

작성자 안민성
