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

## 현재 상태 (2026-09-23 23:40 갱신)

| PR | 상태 | 비고 |
|---|---|---|
| BE #84 Q&A API | **병합 완료** | |
| FE #29 Q&A 화면·SDK | **병합 완료** | 리뷰 12건 반영, SDK UI 디자인 반영 |
| BE #87 운영시간 API | **병합 완료** | `c78ddbe` |
| FE #30 운영시간 화면 | **병합 완료** | base를 `feat/hospital-chatbot`으로 재타깃 후 병합 `3b6aa0e` |

챗봇 Phase 2(관리 기능 기반)는 통합 브랜치 `feat/hospital-chatbot`에 모두 들어갔다.

### 직전 핸드오프의 "바로 해야 할 일" 처리 결과

1. FE #30 base 재타깃 — 완료. `feat/hospital-chatbot-qna` → `feat/hospital-chatbot`.
2. 병합 순서 BE #87 → FE #30 — 완료. BE #87이 먼저 병합된 뒤 FE #30을 병합했다.
3. 수동 E2E — 로컬 DB 마이그레이션 적용과 도메인 레벨 검증까지 완료. **브라우저 화면 확인만 남았다**(staff 로그인 필요).

### 로컬 E2E 수행 결과 (2026-09-23)

`beaulab_frontend_local`에 운영시간 3개 마이그레이션을 적용했다.

- `create_hospital_chatbot_operating_hours_table`
- `create_hospital_chatbot_closure_dates_table`
- `create_hospital_chatbot_settings_table`

적용 후 실제 DB를 대상으로 저장·조회·판정을 확인했다.

| 검증 | 결과 |
|---|---|
| 주간 스케줄 7건 + 휴무일 1건 저장 | 성공 |
| 저장값 재조회 (월 09:00~18:00 / 일 휴무 / 휴무일 사유 한글) | 일치 |
| 수 14:00 | `OPEN` |
| 수 20:00 | `OUTSIDE_HOURS`, next 2026-09-24T09:00+09:00 |
| 일 14:00 | `OUTSIDE_HOURS`, next 2026-09-28T09:00+09:00 |
| 휴무일 2026-09-30 14:00 | `CLOSED_DATE`, next 2026-10-01T09:00+09:00 |
| 즉시 중단 스위치 ON | `FORCE_CLOSED`, `next_open_at` 없음 |
| 즉시 중단 스위치 OFF 복구 | `OUTSIDE_HOURS`로 복귀, 설정 행 초기화 확인 |

`/hospital-chatbot/operating-hours`는 미로그인 시 `/login?next=/hospital-chatbot/operating-hours`로 정상 리다이렉트되고 페이지 타이틀(`상담 운영시간`)까지 해석된다. 라우팅·권한 배선은 확인됐다.

**남은 것**: staff 계정으로 로그인한 뒤 화면에서 요일 저장·휴무일 추가·즉시 중단 토글을 눌러보고, hospital-web SDK 하단 상태 영역이 위 상태값에 맞게 바뀌는지 눈으로 확인한다. 비밀번호 입력이 필요한 단계라 사람이 직접 해야 한다.

### 로컬 서버 기동 메모

- 프론트 worktree에는 `.env.local`이 복사되지 않는다(ignored). `beaulab_new_frontend/apps/*/`.env.local`을 worktree의 같은 경로로 복사해야 `NEXT_PUBLIC_API_URL`이 잡힌다.
- staff-web은 `pnpm exec next dev -p 3010`으로 띄운다. 3010을 여러 worktree가 동시에 쓸 수 없으므로 기존 프로세스를 먼저 내린다.
- API는 backend worktree에서 `php artisan serve --port=8000`.
- Sanctum이 `config/sanctum.php`에서 `'guard' => []`이라 **Bearer 토큰이 통하지 않는다**. API 수동 확인은 쿠키 세션이 필요하고, 도메인 레벨 확인은 `bootstrap/app.php`를 로드하는 스크립트에서 Action을 직접 호출하면 된다.
- `closure_dates` 항목의 키는 `date`다(`closure_date` 아님).

### 이번에 추가된 것

- 상담원 연결 **즉시 중단 스위치**. `hospital_chatbot_settings` 테이블, resolver가 요일·휴무일보다 먼저 검사해 `FORCE_CLOSED` 반환. 날짜별 휴무는 사전 예약, 스위치는 당장 중단용으로 역할을 나눴다.
- 휴무일은 매년 수동 갱신이 필요하다는 점은 그대로다. 공휴일 캘린더 자동 연동은 PRD 4.2 Out of Scope.

### 고친 결함 (테스트 붙이며 발견)

- `closure_dates`가 `required`라 휴무일 전체 해제 저장이 422였다. `present`로 변경.
- Q&A Flow 테스트가 이 저장소 오류 봉투(`error.details.errors`) 대신 Laravel 기본 형식을 검증했다.
- 같은 테스트가 한글 쿼리스트링을 인코딩하지 않아 검색이 0건이었다. **앱 코드는 정상.**
- Q&A DTO 단위 테스트가 실행 환경 시간대에 의존했다. UTC 고정.

### 다음 기능 단계

상담 세션(상담원 연결, 대기·연결·종료, 메시지)은 미착수. SDK의 `상담원 연결` 버튼과 입력창이 여기 포함된다.

전체 잔여 작업은 PRD 17장 티켓 47개 기준으로 완료 8 / 진행중 9 / 남음 30이다.
시각화한 현황판은 저장소 밖 `html/chatbot-roadmap.html`(저장소 상위 폴더)에 있다.

## 결정 사항 (2026-09-23 사용자 확정)

### D-25 인증 Adapter — **현행 유지로 확정**

챗봇은 당분간 **이 사이트(hospital-web) 안에서만 돈다**. 따라서 별도 인증 Adapter를 설계하지 않는다.

- 상담 API도 기존 `/api/v1/hospital/hospital-chatbot/*`와 같은 자리에 `Authenticate:sanctum` + `EnsureActor:hospital`로 붙인다.
- 서버가 쿠키 세션에서 병원을 직접 읽으므로 프론트가 보낸 `hospitalId`를 신뢰하지 않는다. PRD 4.3 요구사항은 이걸로 충족된다.
- 같은 오리진이라 Laravel 브로드캐스팅 인가도 기본 세션으로 동작한다. 실시간 채널 인가를 따로 설계할 필요가 없다.
- **단, SDK가 콜백(`loadQnas` 등)을 주입받는 현재 구조는 그대로 유지한다.** 나중에 병원 쪽 사이트로 독립 배포(D-29)할 일이 생겨도 주입 함수만 교체하면 된다. SDK 내부에서 직접 `fetch`를 호출하기 시작하면 그때 전면 수정이 필요해진다.
- 크로스 오리진으로 나갈 때가 오면 그때 단기 토큰 발급 방식을 다시 검토한다. 그 경우 `config/sanctum.php`의 `'guard' => []`를 손봐야 Bearer가 동작한다.

남은 필수 결정 3건(D-14 Export 범위, D-15 보존 기간, D-21 Q&A 중복)은 Phase 5 전까지 미뤄도 된다.

### 착수 순서 (합의됨)

1. **상담 스키마** — `consultations`, `consultation_messages`, `consultation_status_history`. 병원당 활성 상담 1건 Unique 인덱스를 이때 같이 건다(D-26). 나중에 붙이면 데이터 정리가 필요해진다.
2. **상담 생성·활성 조회** — 운영시간 검증 후 `WAITING` 생성. 중복 클릭·다중 탭은 Unique로 막고 기존 상담을 반환한다.
3. **상태머신과 Claim** — `WAITING → CONNECTED` 원자적 UPDATE(영향 행 0이면 `409 ALREADY_CLAIMED`), 취소, 종료, 상태 이력. **대기 30분 자동 만료 배치(D-07)를 빠뜨리기 쉽다.**
4. **관리자 대기 목록과 연결 버튼** — 실시간 없이 폴링/새로고침으로 먼저. 이 시점에 화면에서 상태 전이를 눈으로 검증할 수 있다.
5. **SDK 연결 요청과 대기 화면** — 상담원 연결 버튼, `WAITING` UI, 입력창 비활성, 취소.

여기까지가 PRD Phase 3이고, 끝나면 실시간 없이도 "요청 → 연결 → 취소"가 양쪽 화면에서 돈다.
이후 Phase 4(메시지·Reverb 실시간) → Phase 5(기록·CSV Export) → Phase 6(통합·QA) 순서다.

Phase 4 착수 전에 Reverb 기동과 운영 배포 방식을 정해야 한다. 로컬에는 Redis만 떠 있고 Reverb는 아직 띄운 적이 없다.

## 검증 관련 주의

- Backend 테스트용 `beaulab_testing` DB와 권한은 컨테이너 MySQL에 준비됐고 전체 37개가 통과한다. 실행 명령은 `vendor/bin/phpunit`이며 `php artisan test`는 이 저장소에 없다.
- 운영시간 작업에서도 원격/공용 DB에는 절대 마이그레이션하지 않는다.
- Backend 운영시간 worktree에는 `composer install`이 완료되어 `vendor/`가 있다(ignored).
- Backend 운영시간 검증 결과: resolver 단위 테스트 3개/12 assertions 통과, 변경 PHP 17개 Pint 통과, 신규 라우트 3개 등록 확인, `git diff --check` 통과.

## 로컬 실행 상태

- MySQL 8.4 / Redis 7은 Docker 컨테이너로 실행한다. MySQL은 호스트 3307에 매핑했다(3306은 다른 프로젝트 MariaDB가 점유).
- compose 파일은 저장소 밖 `docker-compose.yml`(저장소 상위 폴더)에 있다. 비밀번호가 들어 있어 커밋하지 않는다.
- 로컬 개발 DB `beaulab_frontend_local`에는 Q&A 70건이 적재되어 있다.
- API 8000, hospital-web 3002, staff-web 3010. staff-web은 3000이 다른 프로젝트에 선점되어 3010을 쓰며, 그때는 `.env`의 `STAFF_WEB_ORIGINS`에 해당 Origin을 추가해야 한다.
- macOS 환경 구축 기록은 백엔드 `develop-doc/local-setup-macos.md`에 있다.

## 도구 경로

개발자 PC마다 다르므로 절대경로를 기록하지 않는다. 사용하는 도구는 다음과 같고, 설치 위치는 각자 환경에서 확인한다.

- PHP 8.3 이상과 Composer 2 (macOS 설치 기록은 `develop-doc/local-setup-macos.md`)
- GitHub CLI `gh`
- Node와 pnpm. 프론트 저장소는 `packageManager`에 지정된 pnpm 버전을 사용한다.
