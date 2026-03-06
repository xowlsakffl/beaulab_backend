# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 **구조 / 권한 / 운영 규칙**을 정리한 문서 모음입니다.
실제 코드 변화(도메인 추가, 권한 확장, 라우트 변경)를 기준으로 계속 업데이트합니다.

---

## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [에러 / 예외 처리](./error-handling.md)
- [권한 / 메뉴 설계 (Staff / Hospital / Beauty / User)](./authorization.md)
- [로깅 전략 (감사로그 / 운영로그)](./logging.md)
- [도메인 & 상태 정의서](./domain-status-definition.md)
- [카테고리 ERD 정의서 (비개발자용)](./category-erd-definition.md)
---

## 현재 기준 핵심 요약

- API 엔드포인트는 Actor 기준(`staff`, `hospital`, `beauty`, `user`)으로 분리한다.
- 비즈니스 로직은 `app/Domains/*`, API 진입점은 `app/Modules/*`에 둔다.
- 권한 단일 소스는 `AccessPermissions` / `AccessRoles`이며 Seeder로 동기화한다.
- Staff 권한에는 전반적인 관리 권한이 포함된다.
- Hospital/Beauty는 독립 로그인이다.
- 모든 예외 응답은 공통 예외 핸들러/응답 포맷 규칙을 따른다.

---

작성 기준: 2026-03-04



