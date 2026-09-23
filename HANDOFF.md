# Hospital Chatbot Handoff

## 사용자 작업 원칙

- PRD, 작업지시서, `AGENTS.md`를 항상 따른다.
- 챗봇 개발 범위만 변경하며 운영/공용 서버 배포, 원격 DB 마이그레이션·시딩, 원격 큐·Reverb 실행은 하지 않는다.
- 모든 기능 브랜치는 `feat/hospital-chatbot` 또는 직전 스택 브랜치에서 시작한다.
- 기능별로 커밋을 나누고, 완료된 PR은 Draft가 아닌 Ready로 생성한다.
- 코드·스키마·설정 PR에는 실제 수행 결과를 포함한 `Test plan`을 작성한다.
- 검증은 변경 범위에 맞게 최소화한다.
- 사용량이 10% 이하로 남으면 이 파일을 갱신하고 핸드오프한다.

## 완료된 작업

- Backend Q&A PR #84 병합 완료: https://github.com/beaulab/beaulab_new_dev/pull/84
  - 병합 커밋: `f3a95a8`
  - 리뷰 반영 커밋: `2e1d9b1`
- Frontend Q&A PR #29 Ready 상태: https://github.com/beaulab/beaulab_new_frontend/pull/29
  - Staff 화면 권한 누락으로 404 전환되던 문제 수정·푸시 완료
  - 수정 커밋: `8d80536`
  - `http://localhost:3010/hospital-chatbot/qnas` 접근 확인 완료
  - Q&A 화면 데이터가 비어 있는 것은 현재 8000 API가 통합 Q&A 코드를 제공하지 않기 때문이며 화면 라우팅 문제는 해결됐다.

## 현재 작업: 운영시간 Backend

- 저장소: `/Users/sunny/Desktop/부업/beaulab/worktrees/backend-operating-hours`
- 브랜치: `feat/hospital-chatbot-operating-hours`
- 원격 기준 브랜치: `feat/hospital-chatbot`
- 변경사항은 아직 커밋하지 않았다.

### 구현 정책

- 전체 병원 공통 상담 운영시간
- 시간대 `Asia/Seoul`
- 요일별 하나의 연속 시간 구간, 자정 넘김 미지원
- 날짜별 휴무가 주간 운영시간보다 우선하며 공휴일/임시 휴무를 강제로 끌 수 있다.
- 시작 시각 포함, 종료 시각 미포함
- 설정 누락·오류 시 상담원 연결을 닫는 fail-closed 방식
- 운영시간 외 메시지: `상담원 연결 시간이 아닙니다.`
- Q&A는 운영시간과 무관하게 계속 사용할 수 있다.

### 변경 파일

- 운영시간/휴무일 마이그레이션 2개
- 운영시간/휴무일 모델 2개
- `HospitalChatBotAvailabilityResolver`
- Staff 조회·수정 DTO/Query/Action/Request/Controller
- Hospital availability Query/Action/Controller
- Staff/Hospital API 라우트
- resolver 단위 테스트
- `develop-doc/api.md`, `develop-doc/hospital-chatbot-prd.md`

### 다음 순서

1. 변경 PHP 파일에 Pint 검사 실행
2. `HospitalChatBotAvailabilityResolverTest`만 실행
3. 챗봇 라우트 3개 등록 확인 및 `git diff --check`
4. 실패 시 변경 범위 내 수정
5. `feat(chatbot): 상담 운영시간 설정 API 추가`로 커밋
6. 브랜치 푸시
7. base `feat/hospital-chatbot`으로 Ready PR 생성하고 상세 `Test plan` 작성
8. Frontend 운영시간 브랜치를 최신 `feat/hospital-chatbot-qna`로 fast-forward한 뒤 Staff 설정 UI와 SDK 상태 처리를 구현

## 검증 관련 주의

- Backend Q&A HTTP 테스트는 로컬 MySQL의 `beaulab_testing` 권한 부재로 실행하지 못했으며, 이 문제 때문에 과도한 환경 수정은 하지 않는다.
- 운영시간 작업에서도 원격/공용 DB에는 절대 마이그레이션하지 않는다.
- Backend 운영시간 worktree에는 `composer install`이 완료되어 `vendor/`가 있다(ignored).

## 로컬 실행 상태

- API: 8000
- hospital-web: 3002
- staff-web: 3010
- 3010은 현재 frontend Q&A worktree에서 실행 중이다.

## 도구 경로

- PHP/Composer: `/opt/homebrew/bin/php`, `/opt/homebrew/bin/composer`
- gh: `/Users/sunny/.local/bin/gh`
- Node: `/Users/sunny/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node`
- pnpm CLI: `/Users/sunny/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/pnpm/bin/pnpm.cjs`
