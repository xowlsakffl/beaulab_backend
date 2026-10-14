# 구조와 개발 기준

## 기술 스택과 진입점

PHP 8.3+, Laravel 12 기반의 모듈형 단일 서버입니다. MySQL에 업무 데이터를 저장하고 Redis를 웹 세션, 큐, 일부 캐시에 사용합니다.

| 기능               | 구현                                         |
| ------------------ | -------------------------------------------- |
| 인증·권한          | 웹 세션, Sanctum, Spatie Permission          |
| 비동기·실시간      | Redis Queue, Horizon, Reverb                 |
| 조회·로그          | Spatie Query Builder, Activitylog, Telescope |
| API 문서·예약 작업 | Scramble, Schedule Monitor                   |
| 검사               | PHPUnit, Pint, Composer audit                |

정확한 의존성은 [composer.json](../composer.json)과 [composer.lock](../composer.lock), 부트스트랩은 [bootstrap/app.php](../bootstrap/app.php) 및 [Provider 등록](../bootstrap/providers.php)이 기준입니다.

- HTTP: [public/index.php](../public/index.php) → bootstrap → [API 라우트](../routes/api.php) → Actor별 Module.
- CLI: [artisan](../artisan) → [콘솔 명령·스케줄](../routes/console.php).
- 실시간 채널 인가: [routes/channels.php](../routes/channels.php).
- 내부 운영 도구: [웹 라우트](../routes/web.php). 업무 API와 별도 로그인 경계입니다.

## 폴더 역할

```text
app/Modules/{Staff,Hospital,Beauty,User}/
  routes/             Actor별 API 경로와 미들웨어
  Http/Controllers/   Request → Action → ApiResponse
  Http/Requests/      입력 형태·허용값 검증

app/Domains/{Domain}/
  Actions/            업무 흐름·인가·트랜잭션
  Queries/            DB 조회·저장 구현
  Models/             관계·상태·캐스트
  Policies/           작업 권한·대상 접근 조건
  Dto/                클라이언트에 반환할 데이터
  Support, Data 등    해당 도메인에 필요한 보조 코드

app/Domains/Common/   여러 도메인에 붙는 업무 기능
app/Common/           HTTP·인증·예외 등 기술 공통 기능
```

Actor는 요청 주체, Domain은 업무 책임입니다. 같은 Hospital 도메인에도 Staff와 Hospital의 접근 범위가 다릅니다.
Queries에는 기존 저장 메서드도 있으므로 폴더명만으로 읽기 전용이라고 가정하지 않습니다. 모든 도메인에 같은 하위 폴더를 억지로 만들 필요는 없습니다.

## 도메인 찾기

| 관심사           | 위치                                                                                                                                                                                                              |
| ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 계정·직원 권한   | [AccountStaff](../app/Domains/AccountStaff), [AccountUser](../app/Domains/AccountUser), [AccountHospital](../app/Domains/AccountHospital), [AccountBeauty](../app/Domains/AccountBeauty)                          |
| 병의원·뷰티·입점 | [Hospital](../app/Domains/Hospital), [HospitalDoctor](../app/Domains/HospitalDoctor), [HospitalEntry](../app/Domains/HospitalEntry), [Beauty](../app/Domains/Beauty), [BeautyExpert](../app/Domains/BeautyExpert) |
| 이벤트·광고·영상 | [HospitalEvent](../app/Domains/HospitalEvent), [HospitalEventAd](../app/Domains/HospitalEventAd), [HospitalVideo](../app/Domains/HospitalVideo)                                                                   |
| 게시물           | [Talk](../app/Domains/Talk), [HospitalReview](../app/Domains/HospitalReview), [HospitalEvaluation](../app/Domains/HospitalEvaluation), [Notice](../app/Domains/Notice)                 |
| 금액·대화        | [HospitalWallet](../app/Domains/HospitalWallet), [Chat](../app/Domains/Chat), [HospitalChatBot](../app/Domains/HospitalChatBot)                                                                                    |
| 공통 업무        | [Category, Media, AdminNote, OperationHistory, ContentReport, Notification, Sms 등](../app/Domains/Common)                                                                                                        |

병의원 계정과 병의원은 1:1입니다. `account_hospitals.hospital_id`의 unique 제약을 유지하며, 뷰티 계정도 같은 관계입니다.
조회 DTO의 단수 관계를 임의로 배열로 바꾸거나, 계정 표시명을 실제 담당자 이름으로 해석하지 않습니다.

API가 아직 없는 업무도 있습니다. Hospital Module에는 현재 병의원 정보 수정·영상 등록 전체 CRUD가 없고, Beauty Module도 전체 파트너 관리 API가 아닙니다. 실제 범위는 각각의 라우트로 판단합니다.

## 병의원 챗봇 구조

기존 앱에 도메인 하나와 화면 몇 개가 붙은 형태입니다. 새 서비스는 없습니다.

![병의원 챗봇 구조 — 구성, 추가된 테이블 8개, 실시간 처리 방식, 운영에 필요한 프로세스](images/hospital-chatbot-architecture.svg)

읽을 때 짚을 것 네 가지입니다.

- **기존 테이블은 바꾸지 않았습니다.** 새 테이블 8개는 전부 `hospital_chatbot_` 접두사이고, `hospitals`·`account_hospitals`·`account_staffs`는 FK로 참조만 합니다. 병의원명·요청자명·상담원명은 요청 시점 값을 스냅샷 컬럼에 복사하므로 계정을 지워도 상담 기록이 남습니다.
- **실시간 전달:** 메시지 5초·목록 10초 폴링을 기본으로 하고 Reverb 구독을 함께 지원합니다. 종료된 직원 상담창은 폴링을 중단합니다. `NEXT_PUBLIC_REVERB_APP_KEY` 설정과 실제 Reverb·채널 인증 동작을 배포 환경에서 함께 확인해야 하며, 코드 구현만으로 운영 발행을 보장하지 않습니다.
- **상시 프로세스:** 기존 Reverb와 Scheduler를 사용하고, Horizon에 `hospital-chatbot` 큐를 소비하는 전용 `supervisor-hospital-chatbot`을 둡니다. local은 최대 1개, production은 최대 2개 워커이며 일반 사용자 `chat` 큐와 구분합니다. 대기 만료·미발행 재처리는 매분, 지표 기록은 5분마다 실행합니다. 배포 시 설정 캐시를 갱신하고 기존 프로세스 관리자가 Horizon을 다시 띄우도록 `php artisan horizon:terminate`를 실행해야 새 supervisor가 적용됩니다. 이 작업은 배포 승인 후 수행하며, 롤백 시에도 큐에 남은 메시지 발행 작업을 소비할 워커를 유지해야 합니다.
- **기존 인증·개인 권한을 사용합니다.** Sanctum 쿠키 세션·정상 상태와 상담/Q&A/상담 운영관리의 독립 권한 8개를 검사합니다. 답변·종료는 담당자 검사도 유지합니다. 실시간 이벤트는 식별자만 보내며 본문 조회 때 권한을 다시 확인합니다. 상세 권한은 [API 문서](api.md#병의원-챗봇-직원-권한)를 따릅니다.

### 테이블

![병의원 챗봇 ERD — 테이블 8개의 컬럼과 관계](images/hospital-chatbot-erd.svg)

- 컬럼 정의는 [schema.dbml](schema.dbml)의 「병의원 챗봇」 블록에 있습니다. 나머지 92개 테이블과 같은 파일입니다.
- 실제 DDL은 [hospital-chatbot-schema.sql](hospital-chatbot-schema.sql)입니다. 마이그레이션이 정본이므로 보통은 `php artisan migrate`를 쓰고, 이 파일은 스키마를 눈으로 확인하거나 마이그레이션을 돌릴 수 없는 환경에서 씁니다.
- **스냅샷 컬럼**(`*_name_snapshot`, `context_qna_*_snapshot`)은 요청 시점 값을 복사합니다. 계정이나 Q&A를 지워도 상담 기록이 그대로 남습니다.
- **생성 컬럼 두 개**는 MySQL에 Partial Unique Index가 없어서 둡니다. `parent_key`는 `parent_id`가 NULL인 최상위 폴더끼리도 이름 유니크를 걸려고, `active_question_key`는 살아 있는 Q&A끼리만 질문 중복을 막으려고 씁니다. `active_hospital_id`도 같은 수법으로 병의원당 활성 상담 1건을 보장합니다.

연동 계약은 [API 문서](api.md)의 「병의원 챗봇」 절, 요구사항은 [PRD](hospital-chatbot-prd.md), 확인 항목은 [테스트 케이스](hospital-chatbot-test-cases.md)에 있습니다.

## 변경할 때 지킬 기준

1. 같은 Actor의 가까운 도메인에서 Controller → Request → Action → Query → DTO 흐름을 먼저 읽습니다.
2. Request는 형식을 검증하고, Action은 권한·현재 상태·소유권 등 실행 시점의 업무 조건을 검증합니다.
3. 금액, 상태 전이, 중복 신청은 입력 검증만 믿지 않습니다. 필요한 행을 일정한 순서로 잠그고 트랜잭션 안에서 재검증합니다.
4. 업무 변경과 운영 히스토리를 함께 커밋합니다. 외부 발송은 커밋 이후 처리하되, 유실되면 안 되는 발송 대기 기록은 DB에 먼저 남깁니다.
5. DTO에서 추가 쿼리를 실행하지 않습니다. 필요한 관계·집계를 Query에서 준비하고 민감 필드를 명시적으로 제외합니다.
6. 응답 형식과 기존 클라이언트 계약을 유지합니다. 공통화 때문에 필드·상태 의미를 바꾸지 않습니다.

Controller에 업무 계산을 쌓거나 Policy에서 Request 전역 상태를 읽지 않습니다.
반대로 코드 모양이 비슷하다는 이유만으로 검수·광고·신고·정산의 서로 다른 규칙을 범용 서비스 하나로 합치지 않습니다.

## 목록·캐시·성능

- 목록은 제한된 페이지 크기와 안정적인 정렬을 사용합니다. 동률을 구분할 ID 정렬까지 확인합니다.
- 관계는 eager loading, 개수는 필요한 집계로 처리합니다. 전체 관계를 가져와 PHP에서 세지 않습니다.
- 상세의 현재 상태 이력은 최신 관련 1건만 조회하고, 전체 이력은 별도 페이지네이션합니다.
- 검색·필터 허용 필드는 Request/Query에서 제한합니다. 클라이언트 문자열을 그대로 SQL 컬럼·정렬에 사용하지 않습니다.
- 인덱스는 실제 WHERE/ORDER BY와 실행 계획을 보고 결정합니다. 코드만 보고 성능 향상을 확정하지 않습니다.
- Summary 캐시는 기본 300초, 광고 캘린더는 기본 60초이며 변경 시 무효화 경로를 함께 확인합니다. 상세한 장애 구분은 [운영 절차](operations.md)에 있습니다.

도메인별 목록 조회 범위와 상태 차이는 의도된 것일 수 있습니다. 특히 충전금 ALL 탭과 신고 집계를 일반 CRUD에 맞추어 변경하지 않습니다.

## 검증

기존 검사는 [tests](../tests), 환경은 [phpunit.xml](../phpunit.xml), CI는 [tests.yml](../.github/workflows/tests.yml)을 확인합니다.
CI는 MySQL·Redis를 준비하고 PHP 문법, Composer 검증·보안 검사, PHPUnit을 실행합니다.

```sh
composer validate --strict
composer audit --locked
vendor/bin/pint --test <변경한-PHP-파일>
```

PHPUnit 설정은 `beaulab_testing` MySQL DB, Redis DB 15와 테스트 접두사를 사용하고 외부 발송을 비활성화합니다.
테스트가 테이블을 만들거나 데이터를 초기화할 수 있으므로 실제 연결 대상과 개별 테스트의 부트스트랩을 확인한 뒤 실행합니다.

```sh
vendor/bin/phpunit --do-not-cache-result
```

기존 개발 DB나 운영 DB에서 테스트·Seeder를 돌리지 않습니다. 새 테스트는 작업 범위와 사용자 지시에 따라 결정합니다.
문서만 바꾸는 작업은 링크·내용·diff 확인으로 충분하며, 검증하지 않은 API나 실서비스 발송까지 정상이라고 보고하지 않습니다.
