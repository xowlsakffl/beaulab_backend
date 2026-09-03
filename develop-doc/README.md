# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 구조, 권한, 운영 규칙을 정리한 문서 모음이다.  
실제 코드 변화(도메인 추가, 권한 확장, 라우트 변경, 운영 정책 변경)를 기준으로 지속 업데이트한다.

## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [API 응답 / 페이지네이션 규칙](./api-response.md)
- [에러 / 예외 처리](./error-handling.md)
- [권한 / 메뉴 설계 (Staff / Hospital / Beauty / User)](./authorization.md)
- [내부도구 허브 운영 가이드](./internal-tools.md)
- [로깅 전략 (감사로그 / 운영로그)](./logging.md)
- [Queue 운영 가이드](./queue.md)
- [Cache / Redis 적용 규칙](./cache.md)
- [Scheduler 운영 가이드](./scheduler.md)
- [도메인 & 상태 정의서](./domain-status-definition.md)
- [카테고리 설계](./category.md)
- [운영 히스토리 설계](./operation-history.md)
- [콘텐츠 신고 / 신고게시물 관리](./content-report.md)
- [성능 / 인덱스 / 쿼리 규칙](./performance.md)
- [채팅 설계](./chat.md)
- [알림 설계](./notification.md)
- [문자 발송 설계](./sms.md)
- [병의원 충전금 설계](./hospital-wallet.md)
- [병의원 계정 초대와 생성](./hospital-account-invitation.md)
- [병의원 계정 비밀번호 재설정](./hospital-account-password-reset.md)

## 현재 기준 핵심 요약

- API 엔드포인트는 Actor 기준(`staff`, `hospital`, `beauty`, `user`)으로 분리한다.
- 비즈니스 로직은 `app/Domains/*`, API 진입점은 `app/Modules/*`에 둔다.
- Staff API는 대시보드, 병원/입점신청/뷰티/회원/의료진/전문가, 카테고리/해시태그, 병원 이벤트/이벤트 DB/리얼모델 DB, 동영상, 토크/후기/평가/신고, 공지/FAQ 운영을 담당한다.
- Hospital API는 병원 계정 초대 검증/생성, 인증/프로필/비밀번호/관리자 메모와 병원 동영상 운영 기능을 제공한다.
- Beauty API는 현재 뷰티 계정 인증/프로필/비밀번호/관리자 메모를 제공한다.
- User API는 앱 사용자 인증/프로필, 채팅, 토크/후기 작성, 신고, 병원 이벤트 DB/리얼모델 DB 신청, 사용자 차단, 알림을 담당한다.
- 공지사항/FAQ 도메인은 현재 Staff API 기준으로 CRUD와 에디터 이미지를 지원한다.
- FAQ 카테고리는 전용 테이블이 아니라 공통 `Category` 도메인의 `FAQ` 분류를 사용한다.
- 병원/의료진/후기/영상 의료 카테고리는 `HOSPITAL_MEDICAL` 트리를 공유하고, 성형/쁘띠 구분은 `categories.group_code`, 화면별 노출 목록은 `category_usages`로 분리한다.
- 병원/의료진은 신규 생성 시 `NOT_APPLIED`(미신청)를 사용하고, 검수 흐름 진입 후 `PENDING`/`REVIEWING`/`APPROVED`/`REJECTED`를 사용한다.
- 입점신청은 Staff 목록/상세/summary/승인상태 변경 API를 제공하며, 승인상태 변경은 `OperationHistory`에 기록한다.
- 토크/병의원 후기/병의원 평가는 Staff 운영 API와 User 작성 API를 Actor 기준으로 분리한다.
- 병의원 후기는 `HospitalReview`, 댓글은 `HospitalReviewComment`, 병의원 평가는 `HospitalEvaluation` 도메인이 소유한다.
- 신고 기능은 건별 로그(`ContentReport`)와 대상별 현재 상태(`ContentReportState`)를 분리한다.
- 관리자 화면 처리 이력은 `operation_histories` 부모 이력과 `operation_history_changes` 변경 상세로 분리한다.
- LengthAware pagination 목록은 `PaginatedResponse`를 사용하고, 채팅 메시지처럼 cursor 방식인 목록만 예외로 둔다.
- 권한 단일 소스는 `AccessPermissions` / `AccessRoles`이며 Seeder로 동기화한다.
- 신고게시물, 이벤트 DB, 리얼모델 DB, 입점신청처럼 같은 메뉴 그룹에 있어도 업무 책임이 다른 리소스는 별도 권한으로 분리한다.
- Queue 표준 런타임은 Redis + Horizon이며, 현재 실제 사용 큐는 비밀번호 재설정/병의원 계정 초대 메일(`mail`), Push 발송(`notifications`), 공통 문자 발송(`sms`)이다.
- Scheduler는 OS crontab이 매분 `schedule:run`을 실행하고, Laravel Scheduler와 Spatie Schedule Monitor로 관리한다.
- Staff summary count는 `StaffSummaryCache`를 통해 Redis 캐시를 사용하고, 원본 데이터 변경 후 관련 캐시를 무효화한다.
- 모든 예외 응답은 공통 예외 핸들러/응답 포맷 규칙을 따른다.
- 충전금은 업무 상태(`HospitalWalletOperation`)와 완료 원장(`HospitalWalletTransaction`)을 분리한다.
- 목록/summary/selector 성능 규칙과 인덱스 기준은 `performance.md`를 따른다.

작성 기준: 2026-08-12
