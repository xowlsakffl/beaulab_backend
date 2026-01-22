# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 **구조/규칙/운영 기준**을 문서로 정리해두는 공간입니다.  
신규 합류 시 “왜 이렇게 했는지”를 빠르게 이해하고, 팀 내 구현 기준을 통일하는 것을 목표로 합니다.

(개발자 안민성)
## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [에러/예외 처리](./error-handling.md)

## 빠른 요약 (핵심 규칙)

- 관리자 화면은 **Inertia(React) 페이지 렌더링**으로 구성한다. (`/admin/*`)
- 관리자 화면에서 **테이블/필터/모달 CRUD는 관리자 API(`/admin/api/*`)를 호출**한다.
- 앱 사용자 API는 `/api/*`로 분리한다.
- API(`/api/*`, `/admin/api/*`)는 공통 `ApiResponse` 포맷으로 에러를 통일한다.
- Inertia 페이지(`/admin/*`)는 redirect + errors 등 기본 흐름을 깨지 않도록 JSON 에러를 강제하지 않는다.

## 설치된 주요 패키지 (Backend)

- Queue / Monitoring
    - `laravel/horizon` : Redis 큐 모니터링/관리 대시보드
    - `predis/predis` : Redis 클라이언트(큐/캐시 등에서 사용)

- Debug / Observability
    - `laravel/telescope` : 요청/쿼리/잡/예외 등 디버깅/관측 도구 (운영에서는 접근 제어 필요)

- Audit / Logging
    - `spatie/laravel-activitylog` : 관리자/사용자 행위 로그(누가/무엇을/어떻게 변경했는지) 기록

- Query / Filtering
    - `spatie/laravel-query-builder` : 목록 API의 필터/정렬/검색 규칙을 일관되게 구현

작성자 안민성
