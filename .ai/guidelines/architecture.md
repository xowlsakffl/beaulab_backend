# Architecture (구조/흐름)

이 문서는 Beaulab 프로젝트의 **전체 구조와 요청 흐름**을 설명합니다.  
특히 “관리자 화면(Inertia) / 관리자 API / 앱 사용자 API”를 **명확히 분리**하는 것이 핵심입니다.

---

## 1. 큰 그림

본 프로젝트는 아래 3가지 영역으로 구성됩니다.

| 구분 | 목적 | URL Prefix | 응답 |
|---|---|---|---|
| 관리자 페이지 (Inertia) | 관리자 화면 렌더링/페이지 이동 | `/admin/*` | HTML + Inertia |
| 관리자 API | 관리자 화면의 동적 데이터 (테이블/필터/모달 CRUD) | `/admin/api/*` | JSON |
| 앱 사용자 API | 별도 앱(모바일/레거시/외부 클라이언트) 통신 | `/api/*` | JSON |

**핵심 원칙**
- 페이지 네비게이션/폼 흐름은 **Inertia 기본 흐름**을 따른다.
- “동적 UI(테이블/필터/모달)”는 **관리자 API(`/admin/api/*`)**로 처리한다.
- 앱 사용자 API는 **Sanctum 토큰 기반**으로 `/api/*`에 분리한다.

---

## 2. 폴더 구조(백엔드)

- `app/Modules/Admin/*`
    - 관리자 도메인 (세션 기반 인증, 관리자 전용 기능)
- `app/Modules/User/*`
    - 앱 사용자 도메인 (Sanctum 토큰 기반 API)
- `app/Shared/*`
    - 공통: `ApiResponse`, `ErrorCode`, `CustomException`, 공통 유틸/예외 등
- `routes/`
    - `web.php` : 관리자 Inertia 페이지 라우트(`/admin/*`)
    - `admin.php` : 관리자 API 라우트(`/admin/api/*`)
    - `api.php` : 앱 사용자 API 라우트(`/api/*`)

---

## 3. 프론트 구조(Inertia + React)

### 3.1 Inertia 페이지 경로 규칙
- Inertia는 `Inertia::render('{name}')`에서 전달된 name을 기준으로 아래 파일을 로드합니다.

- 페이지 파일 위치:
    - `resources/js/pages/**`

예)
- 서버: `Inertia::render('admin/dashboard')`
- 프론트: `resources/js/pages/admin/dashboard.tsx`

### 3.2 관리자 레이아웃 구성
- 관리자 앱 레이아웃: `resources/js/layouts/admin/app-layout.tsx`
- 관리자 인증 레이아웃: `resources/js/layouts/admin/auth-layout.tsx`
- 관리자 설정 레이아웃: `resources/js/layouts/admin/settings/layout.tsx`

---

## 4. 인증(Authentication)

### 4.1 관리자 (세션 기반)
- Guard: `admin` (session)
- 로그인: `/admin/login` (Fortify)
- 보호 페이지: `/admin/*` + `auth:admin`

### 4.2 앱 사용자 (토큰 기반)
- Guard: `sanctum` (token)
- 보호 API: `/api/*` + `auth:sanctum`
- 토큰 저장: `personal_access_tokens` 테이블

---

## 5. 라우팅/응답/예외 처리 기준

### 5.1 응답 타입 기준
- `/admin/*` : Inertia 응답(HTML/redirect/errors) 유지
- `/admin/api/*` : JSON (공통 포맷)
- `/api/*` : JSON (공통 포맷)

### 5.2 예외 처리의 기본 원칙
- API 구간(`/api/*`, `/admin/api/*`)은 `ApiResponse::error()`로 통일
- Inertia 페이지(`/admin/*`)는 기본 흐름(redirect + errors)을 깨지 않도록 JSON 에러를 강제하지 않음

자세한 내용은 [에러/예외 처리](./error-handling.md)를 참고합니다.

작성자 안민성
