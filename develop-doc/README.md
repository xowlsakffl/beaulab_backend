# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 구조, 권한, 운영 규칙을 정리한 문서 모음이다.  
실제 코드 변화(도메인 추가, 권한 확장, 라우트 변경, 운영 정책 변경)를 기준으로 지속 업데이트한다.

## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [에러 / 예외 처리](./error-handling.md)
- [권한 / 메뉴 설계 (Staff / Hospital / Beauty / User)](./authorization.md)
- [내부도구 허브 운영 가이드](./internal-tools.md)
- [로깅 전략 (감사로그 / 운영로그)](./logging.md)
- [Queue 운영 가이드](./queue.md)
- [Scheduler 운영 가이드](./scheduler.md)
- [도메인 & 상태 정의서](./domain-status-definition.md)
- [채팅 설계](./chat.md)
- [알림 설계](./notification.md)

## 현재 기준 핵심 요약

- API 엔드포인트는 Actor 기준(`staff`, `hospital`, `beauty`, `user`)으로 분리한다.
- 비즈니스 로직은 `app/Domains/*`, API 진입점은 `app/Modules/*`에 둔다.
- 공지사항/FAQ 도메인은 현재 Staff API 기준으로 CRUD와 에디터 이미지를 지원한다.
- FAQ 카테고리는 전용 테이블이 아니라 공통 `Category` 도메인의 `FAQ` 분류를 사용한다.
- 권한 단일 소스는 `AccessPermissions` / `AccessRoles`이며 Seeder로 동기화한다.
- Queue 표준 런타임은 Redis + Horizon이며, Scheduler/Monitor는 별도 문서로 분리 관리한다.
- 모든 예외 응답은 공통 예외 핸들러/응답 포맷 규칙을 따른다.

작성 기준: 2026-03-13

