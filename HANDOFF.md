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

- Frontend Q&A PR #29 후속 반영
  - 리뷰 12건 중 11건 반영: `665540a` (transpilePackages, 순서 상한·빈 값, page 정수 파싱, SDK 오류 메시지·Escape·포커스, 재조회 방지, 분류 그룹핑, 미사용 ref, 테스트 추가)
  - 확정 디자인 반영: `56048b0` (원형 런처, Gradient Header, 서비스 아이콘과 `뷰랩스 챗봇` 명칭, 하단 상태 영역, 패널 폭 340px, 색상 CSS 변수화)
  - 인가 지적 2건은 PRD 4.2·4.3의 범위 밖 판단으로 철회했다.
- 로컬 개발 DB에 Q&A 초기 데이터 적재 완료
  - `beaulab_frontend_local`에 `hospital_chatbot_qnas` 마이그레이션 적용 후 `HospitalChatBotQnaSeeder` 실행
  - 70건 적재, 질문 키 중복 0건. 원본 엑셀의 자사 시트와 질문 단위로 대조해 누락 없음을 확인했다.
- `beaulab_testing` 권한 문제 해결
  - 컨테이너 MySQL에 DB 생성과 `beaulab_frontend` 권한 부여 완료
  - 백엔드 전체 테스트 37개/114 assertions 통과를 확인했다. 이전 핸드오프의 "권한 부재로 실행 불가"는 해소됐다.

## 현재 작업: 운영시간 Frontend

- 저장소: `/Users/sunny/Desktop/부업/beaulab/worktrees/frontend-operating-hours`
- 브랜치: `feat/hospital-chatbot-operating-hours` (프론트)
- base: `feat/hospital-chatbot-qna`
- 기능 커밋: `d9ace0b feat(chatbot): 상담 운영시간 관리 화면과 SDK 상태 처리 추가`
- PR #30: https://github.com/beaulab/beaulab_new_frontend/pull/30

### 구현 범위

- 직원 화면 `/hospital-chatbot/operating-hours`: 요일 7개 토글과 시작·종료 시간, 휴무일 추가·삭제
- 저장 전 검증은 백엔드 `HospitalChatBotOperatingHoursUpdateForStaffRequest`와 같은 규칙을 따른다.
- 설정 없음 또는 운영 요일 0개이면 상담원 연결이 계속 닫힌다는 경고를 표시한다.
- SDK는 선택 어댑터 `loadAvailability`로 상담 가능 여부를 받아 하단 상태 영역에만 반영한다. 조회 실패는 Q&A 사용을 막지 않는다.
- hospital-web이 `GET /api/v1/hospital/hospital-chatbot/availability`를 연결한다.
- 상담원 연결 버튼과 메시지 입력창은 상담 단계라 넣지 않았다.

### 검증

- staff-web·hospital-web·SDK typecheck·lint 통과
- 단위 테스트 35건 통과(운영시간 6건 신규)
- 미실행: 백엔드 #87이 적용된 환경에서의 저장·조회와 SDK 상태 표시 수동 확인

## 이전 작업: 운영시간 Backend

- 저장소: `/Users/sunny/Desktop/부업/beaulab/worktrees/backend-operating-hours`
- 브랜치: `feat/hospital-chatbot-operating-hours`
- 원격 기준 브랜치: `feat/hospital-chatbot`
- 기능 커밋: `2b6e589 feat(chatbot): 상담 운영시간 설정 API 추가`
- Draft WIP PR #87: https://github.com/beaulab/beaulab_new_dev/pull/87
- Backend 구현·최소 검증·푸시·Draft PR 생성까지 완료했다.

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

1. Frontend 운영시간 브랜치 fast-forward 완료(`56048b0` 반영), Staff UI와 SDK 상태 처리 구현 완료.
2. 백엔드 #87의 운영시간 마이그레이션을 로컬 DB에 적용하고, 저장·조회와 SDK 상태 표시를 실제 화면에서 확인한다.
3. 확인 후 PR #87과 프론트 PR #30의 Ready·병합 순서를 사용자 지시에 따라 진행한다. 병합 순서는 백엔드 #87 → 프론트 #29 → 프론트 #30이다.
4. 상담원 연결(상담 세션) 단계는 아직 착수하지 않았다. SDK의 `상담원 연결` 버튼과 입력창이 여기에 포함된다.

## 검증 관련 주의

- Backend 테스트용 `beaulab_testing` DB와 권한은 컨테이너 MySQL에 준비됐고 전체 37개가 통과한다. 실행 명령은 `vendor/bin/phpunit`이며 `php artisan test`는 이 저장소에 없다.
- 운영시간 작업에서도 원격/공용 DB에는 절대 마이그레이션하지 않는다.
- Backend 운영시간 worktree에는 `composer install`이 완료되어 `vendor/`가 있다(ignored).
- Backend 운영시간 검증 결과: resolver 단위 테스트 3개/12 assertions 통과, 변경 PHP 17개 Pint 통과, 신규 라우트 3개 등록 확인, `git diff --check` 통과.

## 로컬 실행 상태

- MySQL 8.4 / Redis 7은 Docker 컨테이너로 실행한다. MySQL은 호스트 3307에 매핑했다(3306은 다른 프로젝트 MariaDB가 점유).
- compose 파일은 저장소 밖 `beaulab/docker-compose.yml`에 있다. 비밀번호가 들어 있어 커밋하지 않는다.
- 로컬 개발 DB `beaulab_frontend_local`에는 Q&A 70건이 적재되어 있다.
- API 8000, hospital-web 3002, staff-web 3010. staff-web은 3000이 다른 프로젝트에 선점되어 3010을 쓰며, 그때는 `.env`의 `STAFF_WEB_ORIGINS`에 해당 Origin을 추가해야 한다.
- macOS 환경 구축 기록은 백엔드 `develop-doc/local-setup-macos.md`에 있다.

## 도구 경로

- PHP/Composer: `/opt/homebrew/bin/php`, `/opt/homebrew/bin/composer`
- gh: `/Users/sunny/.local/bin/gh`
- Node: `/Users/sunny/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node`
- pnpm CLI: `/Users/sunny/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/pnpm/bin/pnpm.cjs`
