# Web Authentication

웹은 Laravel 서버 세션, 사용자 네이티브 앱은 Sanctum Bearer 토큰을 사용한다.
권한 정의와 도메인 API는 공유하며 인증 수단만 분리한다.

## 요청 계약

- 웹 공통 client는 `X-Beaulab-Client: web`과 credentials를 보낸다.
- Next 웹은 동일 출처 `/api/v1/{actor}`를 백엔드로 프록시한다. API 주소는 서버 환경변수 `API_URL`로 설정한다.
- 백엔드는 actor별 허용 origin을 정확히 비교한다. 와일드카드나 전달된 Host를 신뢰해 허용하지 않는다.
- `GET /api/v1/{actor}/auth/csrf`에서 CSRF 토큰을 받아 메모리에 보관하고 변경 요청의 `X-CSRF-TOKEN`으로 전달한다.
- 로그인과 로그아웃도 CSRF 검증 대상이다. 웹 요청에서 Bearer 토큰은 허용하지 않는다.
- 없는 계정과 비밀번호 불일치는 동일한 401/문구로 응답하며 Laravel Timebox로 실패 응답의 최소 처리 시간을 둔다. 기존 로그인 rate limit도 유지한다.
- CSRF 검증 실패는 `CSRF_MISMATCH`(419), 초대/재설정 링크 만료는 `TOKEN_ERROR`로 구분한다.
- 웹 로그인 응답은 `session` 만료 정보와 기존 계정 DTO를 반환하며 `token`은 반환하지 않는다.
- 네이티브 사용자 로그인은 기존 `token` 응답을 유지한다. 직원/파트너 API는 웹 세션만 허용한다.

## 세션 정책

- staff/hospital/beauty: 미사용 2시간, 로그인 시점부터 최대 24시간, 브라우저 세션 쿠키.
- user 웹: 미사용 7일, 로그인 시점부터 최대 30일, 영속 쿠키.
- 값은 `config/web_auth.php`에서 actor별로 관리한다.
- 사용 중에는 미사용 만료 시각을 갱신하지만 최대 만료 시각은 늘리지 않는다. 최대 시간 변경은 다시 로그인해 생성한 세션부터 적용된다.
- 세션은 Redis에 저장한다. 인증 저장소 장애 시 DB/브라우저 토큰으로 우회하지 않는다.
- 쿠키 이름과 경로를 actor별로 분리하고 HttpOnly/SameSite=Lax를 강제한다. 운영 환경에서는 Secure를 강제한다.
- 프론트 로그인 유지 체크박스는 사용하지 않는다. 브라우저 종료 여부가 아닌 서버 만료 검증이 최종 기준이다.
- 메뉴 N 배지와 인증 상태 확인 같은 수동 조회는 미사용 시간을 연장하지 않는다. 사용자 입력 중에는 제한된 주기로 activity 요청을 보낸다.
- 로그아웃은 서버 세션과 로그인 식별자를 폐기한 뒤 다른 탭에 동기화한다. 동시 처리 중이던 요청이 세션을 다시 저장해도 폐기된 로그인은 인증되지 않는다.
- 비밀번호 지문 및 계정 활성 상태를 매 요청 확인하여 비밀번호 변경/재설정, 정지/삭제 후 기존 세션을 차단한다.
- 쿠키 세션의 Sanctum transient token은 모든 ability를 통과하므로 `EnsureActor`에서 실제 모델 유형을 별도로 검사한다.
- 상태 변경 요청을 자동 재전송하지 않는다. CSRF 오류 시 다음 요청용 토큰만 초기화한다.

## 배포

- `STAFF_WEB_ORIGINS`, `HOSPITAL_WEB_ORIGINS`, `BEAUTY_WEB_ORIGINS`, `USER_WEB_ORIGINS`는 쉼표로 구분한 정확한 URL origin이다.
- 운영에서는 실제 HTTPS 웹 origin을 반드시 지정한다. 로컬 origin은 local/testing 환경에서만 기본 제공한다.
- `WEB_SESSION_CONNECTION`은 Redis connection명이며 기본은 `default`다. 운영 Redis는 세션을 임의 eviction하지 않도록 관리한다.
- 기존 웹 토큰은 전환 후 인증에 사용하지 않는다. 전환 시 웹 사용자는 다시 로그인해야 한다.
- 내부 도구 `tool_staff` 세션은 별도이며 API 세션 쿠키와 합치지 않는다.
- 사용자 웹의 기존 2계정 채팅 테스트는 개발 전용 화면이며 네이티브 토큰 API 검증 용도로만 유지한다.
