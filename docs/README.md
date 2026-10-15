# 뷰랩 시스템 기준서

뷰랩의 업무 규칙과 시스템 동작을 설명하는 문서입니다. 채용·인수인계 자료, 작업 지시서, 변경 이력은 포함하지 않습니다.

## 어디서부터 읽나

| 알고 싶은 것                                | 문서                                                                                                                            |
| ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| 뷰랩이 무엇이고 누가 사용하는가             | [시스템 개요](overview.md)                                                                                                      |
| 어떤 기능이 실제로 연결되어 있는가          | [기능 지도](feature-map.md)                                                                                                     |
| 병의원과 계정·의료진은 어떻게 연결되는가    | [병의원](business/hospitals.md)                                                                                                 |
| 이벤트·광고·노출은 어떻게 다른가            | [이벤트와 광고](business/events-advertising.md)                                                                                 |
| 신청·배정·답변·차감은 언제 발생하는가       | [상담신청](business/consultations.md), [충전금과 단가](business/wallet-pricing.md)                                              |
| 회원·게시물·신고는 어떻게 처리되는가        | [회원과 콘텐츠](business/members-content.md), [신고와 제재](business/moderation.md)                                             |
| 역할·개인 권한·내 정보는 어떻게 동작하는가  | [직원과 권한](business/staff-permissions.md)                                                                                    |
| Q&A와 직원 상담은 어떻게 연결되는가         | [챗봇과 상담](business/chatbot.md)                                                                                              |
| 누구에게 알림이 가고 언제 읽음이 되는가     | [알림](business/notifications.md)                                                                                               |
| 카테고리와 앱 메뉴는 무엇이 다른가          | [카테고리](business/categories.md)                                                                                              |
| 금지어·닉네임·공지 설정은 어디에 적용되는가 | [운영 설정](business/operating-settings.md)                                                                                     |
| 대시보드 숫자는 무엇을 집계하는가           | [통계](business/statistics.md)                                                                                                  |
| 서버 구조·HTTP·보안을 확인하고 싶다         | [구조](technical/architecture.md), [API와 보안](technical/api-security.md)                                                      |
| 큐·실시간·메일·SMS를 이해하고 싶다          | [비동기와 외부 연동](technical/async-integrations.md)                                                                           |
| 로컬 실행·샘플 데이터·운영 절차가 필요하다  | [로컬 실행](operations/local-development.md), [개발 데이터](operations/development-data.md), [운영 절차](operations/runbook.md) |
| 변경할 때 어떤 규칙을 지켜야 하는가         | [작업 규칙](working-rules.md), [AGENTS](../AGENTS.md)                                                                           |
| 프론트 구조와 UI 기준을 확인하고 싶다       | [프론트 문서](https://github.com/beaulab/beaulab_new_frontend/blob/master/docs/README.md)                                       |

## 문서의 책임

- 업무 규칙은 이 저장소의 `docs/business/`가 단일 관리 지점입니다. 프론트는 이를 연결하고 화면 구현 규칙만 설명합니다.
- HTTP 필드·타입의 최종 구현은 Request·Controller·DTO·자동 API 문서, DB 제약은 마이그레이션에서 확인합니다. 수동 문서에 모든 필드를 복제하지 않습니다.
- 같은 업무의 화면명, 코드명, 사용자 용어가 다르면 의미를 설명합니다. 예: 고객 DB는 데이터베이스 운영 메뉴가 아니라 상담신청 데이터입니다.
- **구현됨**은 실행 경로가 코드에 연결된 상태를 뜻합니다. 운영 배포·실제 외부 연동 검증까지 완료했다는 뜻은 아닙니다.
- **부분 구현**은 화면·API·내부 처리 중 일부만 연결된 상태, **확인 필요**는 정책 또는 운영 환경을 코드만으로 확정할 수 없는 상태입니다.
- 업무 정책과 코드가 다르면 구현을 곧바로 정책으로 승인하지 않습니다. 해당 문서의 경계·확인 항목에서 구분합니다.
- 변경 이력은 Git·PR에 남깁니다. 문서에는 현재 유효한 동작과 제약만 유지합니다.
