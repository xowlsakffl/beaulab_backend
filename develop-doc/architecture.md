# Architecture (구조 / 흐름)

이 문서는 Beaulab 프로젝트의 **전체 구조와 요청 흐름**을 설명합니다.  
본 프로젝트는 **Laravel + Inertia 기반 SPA 관리자 구조**를 전제로 하며,  
관리자(Admin) / 앱 사용자(User) / 도메인(Domain)을 **명확히 분리**하는 것을 목표로 합니다.

---

## 1. 큰 그림

본 프로젝트는 아래 **2가지 요청 영역**으로 구성됩니다.

| 구분 | 목적 | URL Prefix | 응답 |
|---|---|---|---|
| 관리자 영역 (Inertia SPA) | 관리자 화면 렌더링 + CRUD + 테이블 + 폼 | `/admin/*` | Inertia(Response / Redirect / Errors) |
| 앱 사용자 API | 모바일/외부 클라이언트 통신 | `/api/*` | JSON |

### 핵심 원칙
- **관리자 영역은 전면 Inertia 기반 SPA로 동작**한다.
- 페이지 렌더, 리스트 조회, 필터, 페이지네이션, 생성/수정/삭제를 모두  
  **Inertia 요청 → Inertia 응답** 흐름으로 처리한다.
- 관리자 영역에서는 **JSON API(`/admin/api/*`)를 사용하지 않는다.**
- 앱 사용자 API만 `/api/*` 경로에서 **stateless JSON API**로 제공한다.

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
    - `web.php` : 관리자 Inertia 라우트(`/admin/*`)
- `app/Modules/User/routes/`
    - `api.php` : 앱 사용자 API 라우트(`/api/*`)

---


### 역할 요약
- `Modules/Admin`
    - 관리자 영역 **HTTP 진입점**
    - Inertia 페이지 렌더 및 액션 처리
    - 세션 기반 인증
- `Modules/User`
    - 앱 사용자 API 진입점
    - 토큰 기반 인증
- `Domains`
    - **비즈니스 규칙의 중심**
    - 모델 / 유스케이스 / 정책 / 쿼리
- `Common`
    - 공통 유틸, 예외, API 응답 포맷 등

---

## 3. 핵심 설계 원칙 (중요)

### 3.1 “모델은 무조건 도메인(Domains)”
- 모든 Eloquent Model은 `app/Domains/{Domain}/Models` 아래에 위치한다.
- `Modules/Admin`, `Modules/User`에는 모델을 두지 않는다.

> Admin/User는 “누가 호출하느냐(Actor)”의 차이이고,  
> Hospital/Review 같은 “무엇을 다루느냐(도메인)”가 비즈니스 규칙의 주인공이다.

---

### 3.2 컨트롤러는 얇게, 비즈니스 로직은 Domain Action으로
컨트롤러는 아래 역할만 담당한다.
- FormRequest를 통한 validation / authorize
- Domain Action 호출
- Inertia 응답 또는 redirect 처리

컨트롤러에서 **금지**
- 복잡한 쿼리 작성
- 트랜잭션/상태 머신 로직
- 권한 if 분기 (Policy/FormRequest로 이동)

---

### 3.3 Admin / User 차이는 Action과 DTO로 분리
관리자와 앱 사용자는 같은 도메인을 다루더라도 보통 다음이 다르다.
- 기본 조회 조건
- 허용 필터/정렬
- 노출 가능한 필드

따라서 도메인 내부에서 다음을 분리한다.
- `Actions/Admin/*` / `Actions/User/*`
- `Dto/Admin/*` / `Dto/User/*`

---

### 3.4 공통 쿼리/로직은 Query / Support로 분리
Action 간 중복이 발생하면 아래 레이어로 흡수한다.
- `Queries/*` : 검색 / 필터 / 정렬 / 페이징
- `Supports/*` : 도메인 공통 계산, 변환, 보조 로직

---

## 4. 메소드 / 클래스 명명 규칙

### 4.1 Controller 메소드 (HTTP 진입점)

#### Inertia 페이지 + 액션 통합
패턴:


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


---

## 6. 프론트 구조 (Inertia + React)

### 6.1 Inertia 페이지 경로 규칙
- `Inertia::render('{name}')` 기준으로 페이지 파일을 로드한다.

예:
- 서버: `Inertia::render('admin/hospitals/index')`
- 프론트: `resources/js/pages/admin/hospitals/index.tsx`

---

### 6.2 관리자 레이아웃 구성
- 메인 레이아웃: `resources/js/layouts/admin/app-layout.tsx`
- 인증 레이아웃: `resources/js/layouts/admin/auth-layout.tsx`
- 설정 레이아웃: `resources/js/layouts/admin/settings/layout.tsx`

---

## 7. 인증 (Authentication)

### 7.1 관리자
- Guard: `admin` (session)
- 로그인: `/admin/login` (Fortify)
- 보호 영역: `/admin/*` + `auth:admin`
- 모든 요청은 **Inertia 기반 세션 흐름**을 따른다.

### 7.2 앱 사용자
- Guard: `sanctum` (token)
- 보호 API: `/api/*` + `auth:sanctum`
- 토큰 저장: `personal_access_tokens` 테이블

---

## 8. 응답 / 예외 처리 기준

### 8.1 응답 타입
- `/admin/*`
    - Inertia Response / Redirect / Validation Errors
- `/api/*`
    - JSON (`ApiResponse` 포맷)

### 8.2 예외 처리 원칙
- 관리자 영역은 **Laravel + Inertia 기본 흐름을 절대 깨지 않는다.**
- JSON 에러 포맷은 `/api/*`에서만 강제한다.

자세한 내용은 `error-handling.md`를 참고한다.

---

## 9. 실수 방지 체크리스트

- [ ] 새로운 모델을 `Domains/*/Models`에 만들었는가?
- [ ] 컨트롤러가 비대해지지 않았는가?
- [ ] Admin/User 로직이 Action/DTO로 분리되어 있는가?
- [ ] 관리자 영역에서 JSON API를 만들고 있지 않은가?

---

작성자: 안민성
