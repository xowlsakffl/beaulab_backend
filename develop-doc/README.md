# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 **구조 / 규칙 / 운영 기준**을 문서로 정리해두는 공간입니다.  
신규 합류 시 “왜 이렇게 했는지”를 빠르게 이해하고, 팀 내 구현 기준을 통일하는 것을 목표로 합니다.

(개발자 안민성)

---

## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [에러 / 예외 처리](./error-handling.md)
- [권한 / 메뉴 설계 (Staff / Partner / User)](./authorization.md)
- [로깅 전략 (감사로그 / 운영로그)](./logging.md)

---

## 빠른 요약 (핵심 규칙)

- Laravel은 **API 서버 역할만 담당**한다.
- 모든 클라이언트(Staff Web / Partner Web / User Web / Mobile)는 **외부 프론트엔드**로 분리한다.
- 모든 API 응답은 **`ApiResponse` 포맷**으로 통일한다.
- 예외 처리는 `bootstrap/app.php`에서 **단일 기준**으로 처리한다.
- 권한(Role / Permission)은 **전역 규칙(Common)** 으로 관리한다.

---

## 설치된 주요 패키지 (Backend)

### Queue / Monitoring
- `laravel/horizon`  
  Redis 큐 모니터링 및 관리 대시보드
- `predis/predis`  
  Redis 클라이언트 (큐 / 캐시 등에서 사용)

---

### Debug / Observability
- `laravel/telescope`  
  요청 / 쿼리 / 잡 / 예외 등 디버깅 및 관측 도구  
  ※ 운영 환경에서는 접근 제어 필요

---

### Audit / Logging
- `spatie/laravel-activitylog`  
  관리자 / 사용자 행위 로그 기록  
  (누가, 무엇을, 어떻게 변경했는지 추적)

---

### Query / Filtering
- `spatie/laravel-query-builder`  
  목록 API의 필터 / 정렬 / 검색 규칙을 일관되게 구현

---

작성자 안민성
