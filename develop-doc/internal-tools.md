# 내부도구 허브 운영 가이드

이 문서는 Staff 전용 내부도구 로그인, 공용 허브 화면, Horizon / Telescope / Swagger 연동 방식을 현재 코드 기준으로 정리한다.

작성 기준: 2026-03-13

## 1. 목적

기존 `staff` 인증은 API 토큰(`sanctum`) 기준이다.  
Horizon, Telescope, Swagger 같은 브라우저 기반 운영 도구는 토큰보다 세션 기반 인증이 적합하므로, 내부도구 전용 웹 로그인과 공용 허브를 별도로 둔다.

핵심 목적은 아래 3가지다.

1. 내부 운영 도구를 하나의 로그인 세션으로 묶는다.
2. 도구별로 다른 권한 기준을 두지 않고 `viewTool` Gate로 통일한다.
3. 허용 IP, 세션 로그인, 공용 권한을 함께 적용해 외부 직접 접근을 줄인다.

## 2. 현재 포함 대상

현재 내부도구 허브에서 다루는 대상은 아래와 같다.

1. Horizon
2. Telescope
3. Swagger

참고:

- Horizon, Telescope는 실제 라우트와 보호 설정까지 연결되어 있다.
- Swagger는 아직 실제 패키지/라우트가 붙은 상태는 아니고, 허브 카드와 환경변수 기준만 먼저 준비되어 있다.

## 3. 전체 구조

### 3.1 가드 분리

- API Staff 인증: `staff` guard
  - 드라이버: `sanctum`
- 내부도구 웹 인증: `tool_staff` guard
  - 드라이버: `session`

즉, 모바일/프론트 API 로그인과 내부도구 브라우저 로그인은 같은 Staff 모델을 보지만 인증 방식은 다르다.

이렇게 분리한 이유는 다음과 같다.

1. Horizon, Telescope는 브라우저 페이지라 세션 인증이 자연스럽다.
2. API 토큰 guard를 그대로 붙이면 브라우저 접근 흐름이 불편해진다.
3. 내부 운영도구는 공용 로그인 화면 하나로 묶는 편이 관리가 쉽다.

### 3.2 허브 화면

로그인 성공 후 바로 개별 도구로 보내지 않고 `/staff/tools` 허브 화면으로 보낸다.

허브 화면에서는 아래를 제공한다.

1. 현재 로그인한 Staff 정보
2. 로그아웃 버튼
3. Horizon 카드
4. Telescope 카드
5. Swagger 카드

## 4. 주요 경로

### 4.1 내부도구 로그인/허브

- 로그인 화면: `/staff/tools/login`
- 로그인 처리: `POST /staff/tools/login`
- 내부도구 허브: `/staff/tools`
- 로그아웃: `/staff/tools/logout`

### 4.2 개별 도구

- Horizon: `/horizon`
- Telescope: `/telescope`
- Swagger: `INTERNAL_TOOL_SWAGGER_URL` 값 기준

기존 Horizon 전용 로그인 진입 경로는 아래처럼 내부도구 로그인으로 리다이렉트한다.

- `/staff/horizon/login` -> `/staff/tools/login`
- `/staff/horizon/logout` -> `/staff/tools/logout`

## 5. 접근 제어 방식

내부도구는 아래 3단계를 모두 통과해야 한다.

1. 허용 IP
2. `tool_staff` 세션 로그인
3. `viewTool` Gate 통과

### 5.1 허용 IP

공용 미들웨어:

- `App\Common\Http\Middleware\EnsureInternalToolIpAllowed`

환경변수:

- `INTERNAL_TOOL_ALLOWED_IPS`

예시:

```env
INTERNAL_TOOL_ALLOWED_IPS=127.0.0.1,::1
```

현재 요청 IP가 위 목록에 없으면 403으로 차단한다.

### 5.2 세션 로그인

공용 내부도구 로그인은 `tool_staff` guard를 사용한다.

- provider: `staff`
- driver: `session`

로그인 성공 시 세션을 재생성하고, `last_login_at`을 갱신한다.

### 5.3 공용 Gate

공용 Gate 이름은 `viewTool`이다.

허용 조건:

1. `AccountStaff` 사용자여야 한다.
2. `ACTIVE` 상태여야 한다.
3. 역할이 아래 중 하나여야 한다.
   - `beaulab.super_admin`
   - `beaulab.dev`
4. `INTERNAL_TOOL_ALLOWED_EMAILS`가 비어 있지 않으면 이메일도 허용 목록 안에 있어야 한다.

즉, 현재 기준으로 내부도구는 최고관리자 또는 개발자 계정만 접근 가능하다.

## 6. 로그인 흐름

### 6.1 비로그인 사용자가 내부도구 URL 접근

아래 경로는 비로그인 상태에서 내부도구 로그인 화면으로 보낸다.

1. `/staff/tools`
2. `/horizon` 및 `/horizon/*` (`/horizon/api/*` 제외)
3. `/telescope` 및 `/telescope/*` (`/telescope/telescope-api/*` 제외)

즉, 운영자가 Horizon이나 Telescope URL을 직접 쳐도 먼저 내부도구 로그인 화면으로 이동한다.

### 6.2 로그인 성공 후

로그인 성공 시 바로 개별 도구로 보내지 않고 `/staff/tools` 허브 화면으로 보낸다.

### 6.3 허브에서 도구 선택

허브 카드에서 도구를 선택하면 같은 세션으로 각 도구 페이지에 진입한다.

즉, 한 번 로그인하면 Horizon과 Telescope를 각각 다시 로그인할 필요가 없다.

## 7. 도구별 적용 방식

### 7.1 Horizon

Horizon은 아래 기준으로 보호한다.

1. 라우트 미들웨어
   - `web`
   - `internal_tool.ip`
   - `auth:tool_staff`
2. 패키지 내부 인증 콜백
   - `Horizon::auth(...)`
3. 공용 Gate
   - `viewTool`

즉, 단순히 라우트 미들웨어만 통과한다고 끝나는 것이 아니라, Horizon 내부 인증 단계에서도 `viewTool`을 다시 확인한다.

### 7.2 Telescope

Telescope도 같은 기준을 사용한다.

1. 라우트 미들웨어
   - `web`
   - `internal_tool.ip`
   - `auth:tool_staff`
   - `Laravel\Telescope\Http\Middleware\Authorize`
2. 패키지 내부 인증 콜백
   - `Telescope::auth(...)`
3. 공용 Gate
   - `viewTool`

### 7.3 Swagger

현재는 허브 카드만 먼저 준비된 상태다.

- 환경변수 `INTERNAL_TOOL_SWAGGER_URL`가 비어 있으면 카드 상태를 `미설정`으로 표시한다.
- 값이 있으면 허브에서 바로 이동 가능한 링크로 노출한다.

Swagger 실제 라우트/패키지를 붙일 때도 아래 원칙으로 맞춘다.

1. `web`
2. `internal_tool.ip`
3. `auth:tool_staff`
4. `can:viewTool`

즉, Horizon / Telescope와 같은 내부도구 정책을 그대로 재사용한다.

## 8. 환경변수

현재 내부도구 관련 환경변수는 아래 3개다.

```env
INTERNAL_TOOL_ALLOWED_IPS=127.0.0.1,::1
INTERNAL_TOOL_ALLOWED_EMAILS=
INTERNAL_TOOL_SWAGGER_URL=
```

설명:

1. `INTERNAL_TOOL_ALLOWED_IPS`
   - 내부도구에 접근 가능한 IP/CIDR 목록
2. `INTERNAL_TOOL_ALLOWED_EMAILS`
   - 추가 이메일 화이트리스트
   - 비워두면 역할 기준만 사용
3. `INTERNAL_TOOL_SWAGGER_URL`
   - Swagger 카드 링크
   - 절대 URL 또는 상대 경로 모두 허용

## 9. 운영 규칙

### 9.1 내부도구 신규 추가 원칙

새 내부도구를 추가할 때는 아래 기준을 따른다.

1. 별도 로그인 페이지를 만들지 않는다.
2. `tool_staff` 세션을 재사용한다.
3. `viewTool` Gate를 재사용한다.
4. `internal_tool.ip` 미들웨어를 재사용한다.
5. 가능하면 내부도구 허브(`/staff/tools`)에 카드로 추가한다.

### 9.2 권한을 늘릴 때

현재는 `beaulab.super_admin`, `beaulab.dev`만 허용한다.  
추후 운영 정책 변경으로 내부도구 접근 대상을 넓히려면 `InternalToolServiceProvider`의 `viewTool` Gate만 수정하면 된다.

즉, Horizon / Telescope / Swagger 각각을 따로 수정하지 말고 공용 Gate를 기준으로 조정한다.

### 9.3 세션 테이블 주의

내부도구는 세션 기반 인증이므로 `SESSION_DRIVER=database`인 경우 `sessions` 테이블이 반드시 있어야 한다.

테이블이 없으면 Horizon, Telescope, 내부도구 허브 접근 시 세션 조회 단계에서 DB 에러가 발생한다.

## 10. 실제 사용 순서

운영자가 내부도구를 사용할 때 흐름은 아래와 같다.

1. 허용 IP 환경에서 `/staff/tools/login` 접속
2. Staff 계정으로 로그인
3. `viewTool` Gate 통과 확인
4. 내부도구 허브(`/staff/tools`) 진입
5. Horizon / Telescope / Swagger 중 필요한 도구 선택

## 11. 관련 파일

핵심 파일은 아래와 같다.

- `app/Providers/InternalToolServiceProvider.php`
- `app/Providers/HorizonServiceProvider.php`
- `app/Providers/TelescopeServiceProvider.php`
- `app/Common/Http/Middleware/EnsureInternalToolIpAllowed.php`
- `app/Modules/Staff/Http/Controllers/Tool/ToolAuthController.php`
- `routes/web.php`
- `bootstrap/app.php`
- `config/auth.php`
- `config/horizon.php`
- `config/telescope.php`
- `resources/views/tools/login.blade.php`
- `resources/views/tools/index.blade.php`

## 12. 요약

현재 내부도구 구조는 아래 한 문장으로 정리할 수 있다.

`tool_staff` 세션 로그인 + `internal_tool.ip` + `viewTool` Gate를 공용 기준으로 사용하고, 로그인 후에는 내부도구 허브에서 Horizon / Telescope / Swagger를 같은 세션으로 진입한다.
