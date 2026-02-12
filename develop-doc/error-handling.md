# Error Handling

이 문서는 Beaulab 프로젝트의 **공통 예외 처리 구조와 규칙**을 설명합니다.  
신규 합류 개발자 또는 추후 유지보수 시, **왜 이런 구조를 선택했는지**를 빠르게 이해하는 것을 목표로 합니다.

---

## 1. 목적

- API 에러 응답 포맷을 **완전히 통일**
- Actor(Staff / Partner / User) 간 **일관된 에러 처리 기준 유지**
- 예외 처리를 **단일 지점(bootstrap/app.php)** 에서 관리
- 운영 시 에러를 **traceId 기준으로 추적 가능**하게 함

---

## 2. 적용 범위

본 프로젝트는 **API-only 구조**이며, 모든 보호 엔드포인트는 JSON으로 응답합니다.

| 구분 | URL | 응답 형식 |
|---|---|---|
| Staff API | `/api/v1/staff/*` | JSON |
| Partner API | `/api/v1/partner/*` | JSON |
| User API | `/api/v1/user/*` | JSON |

---

## 3. 기본 에러 응답 포맷

모든 API 에러는 아래 형식을 따른다.

    {
      "success": false,
      "error": {
        "code": "USER_NOT_FOUND",
        "message": "사용자를 찾을 수 없습니다."
      },
      "traceId": "c2f1a3b4-..."
    }

규칙:
- `success` : 항상 false
- `error.code` : 내부 ErrorCode 식별자
- `error.message` : 사용자에게 노출 가능한 메시지
- `traceId` : 요청 단위 추적 식별자

---

## 4. JSON 응답 반환 기준

- 모든 API 요청은 **항상 JSON**으로 응답한다.
- Accept 헤더와 무관하게 ApiResponse 규칙을 따른다.

---

## 5. 예외 처리 위치 (중요)

- 예외 처리는 **`bootstrap/app.php` 한 곳**에서만 정의한다.
- 별도의 ApiExceptionRenderer / Handler 분기 클래스를 두지 않는다.
- 예외 → ErrorCode → HTTP Status → ApiResponse 매핑을 **코드로 고정**한다.

---

## 6. traceId 정책 (요청 추적)

### 6.1 RequestId 미들웨어

- 요청 헤더 `X-Request-Id`가 있으면 해당 값을 사용
- 없으면 서버에서 UUID 생성
- 생성/확정된 traceId는:
    - request attribute로 저장
    - response header `X-Request-Id`로 반환
    - 모든 에러 JSON 응답에 포함

### 6.2 로그 컨텍스트

예외 발생 시 로그에는 최소한 아래 컨텍스트를 포함한다.

- traceId
- path
- method

운영 환경에서 **단일 요청 단위 추적**을 가능하게 한다.

---

## 7. 예외 → ErrorCode 매핑 규칙

본 프로젝트는 예외를 아래 규칙으로 ErrorCode 및 HTTP Status에 매핑한다.

| 예외                              | HTTP | ErrorCode | 비고                            |
|---------------------------------|-----:|--|-------------------------------|
| `ValidationException`           |  422 | `INVALID_REQUEST` | details에 validation errors 포함 |
| `AuthenticationException`       |  401 | `UNAUTHORIZED` | 인증 필요                         |
| `AuthorizationException`        |  403 | `FORBIDDEN` | 권한 없음                         |
| `Spatie UnauthorizedException`  |  403 | `FORBIDDEN` | 권한 없음                         |
| `ModelNotFoundException`        |  404 | `NOT_FOUND` | 리소스 없음                        |
| `MethodNotAllowedHttpException` |  405 | `METHOD_NOT_ALLOWED` | 허용되지 않은 메서드                   |
| Rate Limit (429)                |  429 | `RATE_LIMITED` | 요청 과다                         |
| 토큰 오류                           |  419 | `TOKEN_ERROR` | 토큰 무효                         |
| `QueryException`                |  500 | `DB_ERROR` | 운영환경 상세 노출 금지                 |
| `CustomException`               |    - |  | 커스텀 제어                        |
| 기타 `Throwable`                  |  500 | `INTERNAL_ERROR` | 알 수 없는 서버 오류                  |

> 비즈니스 규칙 위반은 **CustomException**을 정의하고  
> 해당 Exception이 직접 ErrorCode를 반환하도록 한다.

---

## 8. Actor별 메시지 정책

같은 ErrorCode라도 **노출 메시지는 Actor에 따라 다를 수 있다**.

- User API
    - 짧고 안전한 메시지
    - 민감 정보 노출 최소화
- Staff / Partner API
    - 운영에 도움이 되는 힌트 허용
    - SQL, 토큰, 개인정보는 금지

원칙:
- 메시지는 ErrorCode 기본 메시지를 사용
- Actor별 커스터마이징이 필요하면 **응답 단계에서 분기**

---

## 9. 디버그 정보 노출 정책

원칙:
- 운영 환경에서는 상세 에러 정보를 응답에 포함하지 않는다.

허용 범위:
- `APP_DEBUG=true` 인 경우
- Staff / Partner API에 한해 제한적으로 허용

예:
- `QueryException` : sql, bindings
- `Throwable` : exception class, message

---

## 10. 샘플 에러 시나리오

### Validation 실패
- HTTP 422
- ErrorCode: INVALID_REQUEST
- details에 필드별 오류 포함

### 인증 실패
- HTTP 401
- ErrorCode: UNAUTHORIZED

### 서버 오류
- HTTP 500
- ErrorCode: INTERNAL_ERROR
- 운영 환경에서는 고정 메시지 사용

---

## 11. 운영 팁

- 클라이언트(Web / App)는 에러 발생 시 **traceId를 함께 로깅/리포트**
- 서버 로그는 traceId 기준 검색이 가능해야 한다.
- API 응답에는 **민감정보(토큰, 전체 SQL, 개인정보)** 가 포함되지 않도록 주의한다.

---

작성 기준: 2026-01-23
