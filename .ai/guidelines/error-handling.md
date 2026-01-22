# Error Handling

이 문서는 본 프로젝트의 **공통 예외 처리 구조와 규칙**을 설명합니다.  
신규 합류 개발자 또는 추후 유지보수 시, **왜 이런 구조를 선택했는지**를 빠르게 이해하는 것을 목표로 합니다.

---

## 1. 목적

- API 에러 응답 포맷을 **완전히 통일**
- 앱 사용자 API와 관리자 API를 **명확히 분리**
- Inertia 기반 관리자 페이지 흐름을 **깨지지 않게 보호**
- 운영 시 에러를 **traceId 기준으로 추적 가능**하게 함

---

## 2. 적용 범위

| 구분 | URL | 응답 형식 |
|----|----|----|
| 앱 사용자 API | `/api/*` | JSON |
| 관리자 API | `/admin/api/*` | JSON |
| 관리자 페이지 (Inertia) | `/admin/*` | HTML / Inertia 응답 |

> `/admin/*` 페이지 라우트에는 JSON 에러를 강제하지 않음  
> (Inertia 페이지 흐름 보호 목적)

---

## 3. 기본 에러 응답 포맷

모든 API 에러는 아래 형식을 따릅니다.

> json { "success": false, "error": { "code": "USER_NOT_FOUND", "message": "사용자를 찾을 수 없습니다." }, "traceId": "c2f1a3b4-..." }

---

## 4. JSON 응답을 반환하는 기준

본 프로젝트는 “요청이 API인지”를 아래 기준으로 판단하여 JSON 에러 응답을 반환합니다.

### 4.1 경로 기반 (강제 JSON)
- 앱 사용자 API: `/api/*`
- 관리자 API: `/admin/api/*`

위 경로는 **항상 JSON**으로 렌더링합니다.

### 4.2 헤더 기반 (선택 JSON)
- 위 경로가 아니더라도, 클라이언트가 `Accept: application/json`을 보내면 JSON으로 응답합니다.

> React에서 `fetch/axios` 호출 시 `Accept: application/json`을 명시하면  
> `/admin/*` 페이지 내부에서도 “데이터 호출”에 한해 JSON 에러 포맷을 사용할 수 있습니다.  
> 단, **페이지 네비게이션 자체(`/admin/*`)는 Inertia 기본 흐름을 유지**합니다.

---

## 5. Inertia 페이지를 보호하는 이유

`/admin/*`는 Inertia 기반 페이지 라우트이며, Laravel의 기본 동작(redirect + errors)이 UX에 최적화되어 있습니다.

- Validation 실패: redirect back + errors 공유
- 인증 실패: 로그인 페이지로 redirect
- CSRF(419) 등 웹 흐름: 기본 핸들링 유지

따라서 `/admin/*`에 대해 JSON 에러를 무조건 강제하면 아래 문제가 발생할 수 있습니다.

- 화면 전환이 깨지거나(JSON만 찍힘), 폼 오류 표시 흐름이 꼬임
- 로그인/권한 오류가 redirect가 아니라 JSON으로 반환되어 UX가 어색해짐

결론: **API는 JSON 통일 / 페이지는 Inertia 기본 흐름 유지**가 안정적입니다.

---

## 6. traceId 정책 (요청 추적)

### 6.1 RequestId 미들웨어
- 요청 헤더 `X-Request-Id`가 있으면 그 값을 사용
- 없으면 서버에서 UUID를 생성
- 생성/확정된 traceId는:
  - request attribute로 저장되어 어디서든 접근 가능
  - response 헤더 `X-Request-Id`로 내려줌
  - JSON 응답에는 `traceId` 필드를 포함

### 6.2 로그 컨텍스트
예외 발생 시 로그에는 최소한 아래 컨텍스트가 포함됩니다.

- traceId
- path
- method

운영에서 “한 번의 요청”을 traceId 기준으로 빠르게 추적할 수 있도록 합니다.

---

## 7. 예외 → ErrorCode 매핑 규칙

본 프로젝트는 예외를 아래 규칙으로 `ErrorCode`와 HTTP status에 매핑합니다.

| 예외 | HTTP | ErrorCode | 비고 |
|---|---:|---|---|
| `ValidationException` | 422 | `INVALID_REQUEST` | `details`에 validation errors 포함 |
| `AuthenticationException` | 401 | `UNAUTHORIZED` | 로그인 필요 |
| `AuthorizationException` | 403 | `FORBIDDEN` | 권한 없음 |
| `ModelNotFoundException` | 404 | `NOT_FOUND` | 리소스 없음 |
| `CustomException` | ErrorCode에 따름 | ErrorCode에 따름 | 도메인/비즈니스 에러 |
| `QueryException` | 500 | `DB_ERROR` | 운영에서는 상세 노출 금지 |
| 기타 `Throwable` | 500 | `INTERNAL_ERROR` | 알 수 없는 서버 오류 |

---

## 8. 관리자 vs 앱 메시지 정책

같은 ErrorCode라도, 사용자에게 노출되는 메시지는 대상에 따라 다릅니다.

- 앱 사용자(`/api/*`): 짧고 안전한 메시지 (민감정보 노출 최소화)
- 관리자(`/admin/api/*`): 운영에 도움이 되는 힌트를 조금 더 허용 (그래도 민감정보는 금지)

예)
- 앱: “서버 오류가 발생했습니다.”
- 관리자: “서버 오류(관리자) - 로그를 확인하세요.”

---

## 9. 디버그 정보 노출 정책

원칙: **운영환경에서는 상세 에러(쿼리/바인딩/스택)를 API 응답으로 내리지 않습니다.**

예외적으로, 관리자 API이고 `APP_DEBUG=true`인 경우에만 제한적으로 details를 포함할 수 있습니다.
- QueryException: sql, bindings 등
- Throwable: exception class, message 등

---

## 10. 샘플 응답

### 10.1 Validation (422)

>json { "success": false, "error": { "code": "INVALID_REQUEST", "message": "요청 값이 올바르지 않습니다.", "details": { "email": ["이메일 형식이 올바르지 않습니다."] } }, "traceId": "..." }


### 10.2 Unauthorized (401)
>json { "success": false, "error": { "code": "UNAUTHORIZED", "message": "인증이 필요합니다." }, "traceId": "..." }


### 10.3 Internal Error (500)
>json { "success": false, "error": { "code": "INTERNAL_ERROR", "message": "서버 오류가 발생했습니다." }, "traceId": "..." }


---

## 11. 운영 팁

- 클라이언트(앱/관리자 프론트)는 에러 발생 시 `traceId`를 함께 로그/리포트에 남길 것
- 서버 로그는 `traceId`로 검색 가능한 형태를 유지할 것
- API 응답에는 민감정보(토큰/SQL 전체/개인정보)가 포함되지 않도록 주의할 것

작성자 안민성
