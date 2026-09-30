# 운영 절차

실행 환경 확인 없이 복구 명령·Seeder·마이그레이션을 실행하지 않습니다.
아래 명령은 운영 절차를 설명하는 예시이며 일괄 실행 스크립트가 아닙니다.

## 프로세스와 설정

| 프로세스  | 역할                            |
| --------- | ------------------------------- |
| API 서버  | HTTP 요청 처리                  |
| Horizon   | Redis 큐 소비와 작업 모니터링   |
| Reverb    | WebSocket 전달                  |
| Scheduler | 예약 작업·미전달 복구·파일 정리 |

큐 설정은 [queue.php](../config/queue.php), [horizon.php](../config/horizon.php)와 각 Job이 기준입니다.
주요 업무 큐는 mail/sms/chat/notifications입니다. Horizon에 정의된 나머지 큐와 개별 Job의 connection/queue도 확인합니다.
동일 업무를 별도 영구 queue:work 구성과 중복 운영해 관리 경계를 흐리지 않습니다.

- Job의 HTTP timeout, Job timeout, Redis retry_after가 충돌하지 않아야 합니다. 긴 외부 요청을 붙일 때 함께 확인합니다.
- Redis `retry_after` 기본값은 360초로, Horizon의 최대 timeout 300초보다 길게 둡니다. 기존 환경의 `REDIS_QUEUE_RETRY_AFTER`가 기본값을 덮어쓰므로 배포 시 실제 설정을 확인하고 config 캐시와 워커를 갱신합니다. 실행 시간을 늘리면 재예약 간격도 함께 조정합니다.
- 업무 데이터 저장 뒤 큐 dispatch 성공만으로 사용자 전달 성공을 판단하지 않습니다.
- 커밋 이후 dispatch와 DB의 재처리 대상 기록을 함께 사용합니다. 상세 전달 보장은 [메시징](messaging.md)에 있습니다.
- 긴 수명의 Horizon/Reverb는 코드·config 변경 뒤 재시작해야 합니다.
- `php artisan horizon:terminate`는 종료 명령입니다. 자동 재기동할 프로세스 관리자가 있을 때 배포에 사용합니다.
- `php artisan reverb:start`는 별도 프로세스입니다. API가 살아 있다는 것만으로 실시간 연결을 보장하지 않습니다.

운영 Scheduler는 매분 프로젝트의 `php artisan schedule:run`을 호출합니다.
로컬의 `php artisan schedule:work`도 실제 예약 작업을 실행합니다. 파일 삭제·DB 갱신·메일/알림 재큐잉이 발생할 수 있으므로 공유 환경에서 점검용으로 켜지 않습니다.
여러 서버에서 중복 실행하지 않도록 배치 구조와 공유 잠금 저장소를 확인합니다.

[콘솔 명령·스케줄](../routes/console.php)에는 다음 종류가 등록되어 있습니다.

- 매분: SMS, Push, 채팅 미전달 복구.
- 주기적: 파일 삭제 대기·미참조 업로드·에디터 임시 이미지 정리, Horizon 지표.
- 일간: 예약 작업 모니터 동기화, 실패/배치 기록 정리, 병원 평점 보정, 충전금 무결성 감사.

시간은 앱의 timezone을 따릅니다. 정확한 시각과 보존 기간은 위 소스와 실제 `schedule:list`로 확인합니다.

## 조회와 재처리를 구분할 것

상태 확인용:

```sh
php artisan migrate:status
php artisan horizon:status
php artisan queue:failed
php artisan schedule:list
php artisan hospital-wallet:audit-integrity
php artisan media:privatize --limit=500
```

무결성 감사는 위반 시 로그와 실패 코드를 남기지만 업무 데이터를 수정하지 않습니다.
media:privatize는 --apply가 없으면 후보만 보여주는 dry run입니다.

아래는 데이터를 바꾸거나 외부 전달을 발생시키는 명령입니다.

| 명령                                            | 부작용                                                                   |
| ----------------------------------------------- | ------------------------------------------------------------------------ |
| `notifications:send-pending-push`               | Push 재큐잉·실제 전송 가능                                               |
| `chat:dispatch-pending-broadcasts`              | 저장된 메시지 재발행 가능                                                |
| `sms:dispatch-pending`                          | SMS 복구 상태 변경·재큐잉·전송 가능                                      |
| `queue:retry <id>`                              | 실패 작업의 업무 로직을 다시 실행                                        |
| `media:cleanup-files`                           | 물리 파일 삭제와 업로드 정리                                             |
| `media:privatize --apply`                       | 파일 복사·DB 디스크 변경·원본 삭제 대기                                  |
| `media:generate-variants`                       | 이미지 생성 및 metadata 변경. limit는 전체 종료 건수가 아니라 chunk 크기 |
| `hospital-evaluations:refresh-hospital-ratings` | 저장된 평점 집계 갱신                                                    |
| `schedule-monitor:sync`                         | 모니터링 대상 레코드 갱신                                                |
| `migrate`, `db:seed`                            | 스키마 또는 업무 데이터 변경                                             |

실패 원인, 멱등 키, 업체 수락 여부를 확인하지 않고 `queue:retry all`을 실행하지 않습니다.
삭제·prune 명령은 감사/장애 분석에 필요한 기록의 보존 기간을 먼저 검토합니다.

## 내부 운영 도구

| 경로                                 | 용도                  |
| ------------------------------------ | --------------------- |
| `/staff/tools/login`, `/staff/tools` | 도구 로그인과 허브    |
| `/horizon`                           | 큐 상태·실패 작업     |
| `/telescope`                         | 제한된 요청·쿼리 진단 |
| `/docs/api`, `/docs/api.json`        | Scramble API 문서     |

[InternalToolServiceProvider](../app/Providers/InternalToolServiceProvider.php)와 [internal_tool.php](../config/internal_tool.php)가 기준입니다.
도구는 tool_staff 세션을 사용하며 Staff API 웹 세션과 별개입니다.

- 활성 AccountStaff이며 super_admin 또는 dev 역할이어야 합니다.
- viewTool Gate와 IP 허용 목록을 통과해야 합니다. 이메일 허용 목록을 설정한 경우 그 조건도 추가됩니다.
- `INTERNAL_TOOL_ALLOWED_IPS`, `INTERNAL_TOOL_ALLOWED_EMAILS`, `INTERNAL_TOOL_API_DOCS_URL`을 확인합니다.
- 허용 IP를 비워 전체 공개하는 방식으로 사용하지 않습니다. 프록시 환경에서는 서버가 판단하는 클라이언트 IP를 확인합니다.
- 도구 역할 접근 규칙을 일반 업무 API의 권한 검사 방식으로 확산하지 않습니다.

## 로그와 민감 정보

| 확인 대상           | 자료                                   |
| ------------------- | -------------------------------------- |
| 요청 실패           | Laravel 로그, traceId/X-Request-Id     |
| 모델 감사           | Activitylog                            |
| 운영 화면 변경 이력 | OperationHistory                       |
| 비동기 실패         | failed_jobs, Horizon, 각 Delivery 상태 |
| 예약 작업           | Schedule Monitor                       |
| 상세 진단           | 제한된 Telescope 기록                  |

운영은 `APP_DEBUG=false`가 필수입니다. debug 응답에는 SQL·bindings 등이 포함될 수 있으므로 공개 환경에서 켜지 않습니다.
traceId와 업무 ID·상태·시도 횟수를 중심으로 원인을 연결하고, 토큰·인증번호·비밀번호·계좌·메일 원문·전체 요청 본문을 기록하지 않습니다.

[TelescopeServiceProvider](../app/Providers/TelescopeServiceProvider.php)에는 인증 경로, 메일·외부 HTTP·Redis/캐시 원문, 비밀 컬럼 SQL의 수집 제한이 있습니다.
로컬이라는 이유로 모든 수집을 안전하다고 가정하지 않습니다. 기능을 늘릴 때 마스킹/제외 경로도 확인합니다.
로그 회전·접근권·보존 기간은 배포 환경에서 별도로 설정해야 합니다.

과거 로그·백업에 이미 남은 비밀 정보는 코드 수정으로 사라지지 않습니다.
보존·사고 대응 정책에 맞춰 해당 필드/레코드를 정리하고 필요한 토큰 폐기·비밀번호 재설정을 수행합니다. 전체 업무 감사 로그를 일괄 삭제하지 않습니다.

## 캐시

일반 조회 캐시와 보안 저장소를 구분합니다.

- Staff Summary는 [공통 Cache](../app/Domains/Common/Cache)와 관련 config를 사용합니다. 기본 TTL은 300초입니다.
- 광고 캘린더는 버전 기반 캐시와 기본 60초 TTL을 사용합니다.
- 쓰기 커밋 후 관련 캐시를 무효화하고 필터·기간·권한에 따라 달라지는 결과를 잘못 공유하지 않습니다.
- 읽기 캐시 장애 시 DB fallback하는 경로가 있습니다. 이 동작을 웹 세션·폐기 저장소에 적용하지 않습니다.
- 보안 Redis가 실패하면 인증은 거부해야 합니다. 세션 복구 실패를 로그인 유지로 처리하지 않습니다.
- 캐시의 정원·잔액·권한을 쓰기 판단의 최종 근거로 삼지 않습니다.

Redis 전체 flush를 일상적인 오류 해결법으로 사용하지 않습니다. 큐·세션·캐시가 공유할 수 있으므로 연결과 prefix를 확인하고 대상 범위만 무효화합니다.

## 파일 수명과 비공개 저장

[media.php](../config/media.php)의 소유 모델/collection 조합으로 민감 파일을 결정합니다.
사업자·면허·환불 서류, 리얼모델 신청 이미지, 평가 영수증, 채팅 첨부 등이 대상입니다.

- 기본 비공개 디스크는 `storage/app/private/media`입니다. 공개 symlink를 만들지 않습니다.
- 다중 서버는 동일한 비공개 파일에 접근할 수 있는 저장소를 구성해야 합니다.
- 민감 파일의 path/variant path는 기본 5분 유효 서명 URL입니다. 만료하면 권한 있는 상세 API를 재조회합니다.
- 서명 URL을 가진 사람은 유효기간 동안 접근할 수 있습니다. 로그·메신저 등에 전달하지 않습니다.
- 공개 이미지의 장기 캐시 설정을 민감 파일이나 JSON API에 적용하지 않습니다.
- 삭제는 DB의 media_file_deletions에 기록하고 커밋된 기록만 물리 삭제합니다.
- 신규 업로드 정리 기록은 DB 롤백과 독립된 비공개 파일에 남습니다. 24시간 경과 후 DB 참조가 없는 업로드를 정리합니다.
- 24시간 이상 열린 업로드 트랜잭션은 정리 작업과 충돌할 수 있으므로 지원되는 정상 흐름으로 보지 않습니다.
- 예전 직원 구분 없는 에디터 임시 파일은 다시 업로드해야 합니다.

### 기존 공개 민감 파일 이전

신규 업로드만 비공개로 바꾸어서는 기존 공개 파일의 접근이 차단되지 않습니다.

1. DB·파일을 백업하고 대상 collection과 저장소 권한을 확인합니다.
2. 쓰기와 관련 정리 작업을 통제한 상태로 신규 코드·마이그레이션을 적용합니다.
3. `php artisan media:privatize --limit=500`으로 후보를 확인합니다.
4. `php artisan media:privatize --apply --limit=500`으로 복사합니다. SHA-256 일치 확인 후 DB 디스크를 변경하고 이전 파일의 삭제를 예약합니다.
5. 후보가 없어질 때까지 반복합니다. 누락 원본·복사 오류를 먼저 해결합니다.
6. 허가된 화면의 조회/다운로드를 확인하고 `media:cleanup-files`로 이전 원본을 정리합니다.
7. CDN·프록시·공개 버킷 복제본도 별도 제거하고 예전 공개 URL이 닫혔는지 확인합니다.

soft-deleted Media와 DB 참조 없는 과거 공개 파일은 이 이전 명령의 대상이 아닙니다.
보존 정책에 따라 별도로 처리해야 합니다. 파일 이전 후 구 코드만 다시 배포하면 private 디스크를 읽지 못할 수 있습니다.

## 배포 확인 순서

1. DB·스토리지 백업과 복구 경로를 준비합니다. 스키마 변경 크기와 ALTER 잠금은 운영 유사 환경에서 확인합니다.
2. 쓰기와 기존 워커의 중첩을 통제합니다. 특히 광고 구좌 잠금 도입 전후 코드를 동시에 쓰기 처리에 투입하지 않습니다.
3. 잠금 파일 기준 `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`를 실행합니다.
4. 적용할 migration을 확인한 뒤 `php artisan migrate --force`를 실행합니다. 전체 DB 초기화·가상 데이터 Seeder를 사용하지 않습니다.
5. Origin·메일·비공개 디스크·내부 도구·Redis 설정을 확인하고 `php artisan config:cache`를 적용합니다.
6. 필요한 파일 이전을 수행하고 API/Horizon/Reverb를 새 코드로 구동합니다. 모든 업무 큐 소비자를 확인합니다.
7. Schedule Monitor 대상 동기화와 schedule:list, 큐 실패/적체, 충전금 무결성을 확인합니다.
8. 승인된 테스트 계정/수신처로 로그인·권한·파일 접근·주요 흐름을 점검합니다. 실발송·실정산은 별도 통제합니다.

DatabaseSeeder의 local/testing 제한은 안전장치이지 운영 데이터 갱신 명령이 아닙니다.
AuthorizationSeeder는 역할 권한을 덮어쓸 수 있고 CategorySeeder는 분류 데이터를 정리하므로 배포의 기본 단계로 넣지 않습니다.

### 개발 단계의 마이그레이션 기준

현재는 컬럼·인덱스 변경을 해당 테이블의 최초 생성 마이그레이션에 합칩니다. 별도 `add`/`alter` 파일은 만들지 않고 신규 테이블만 `create` 파일로 분리합니다. 운영 배포가 시작되면 이미 배포된 스키마의 변경 절차를 별도로 정해야 합니다.

- 활동로그의 `event`·`batch_uuid`, 알림 발송 복구 필드, 채팅 `broadcast_pending`은 각 생성 파일에 포함합니다. `broadcast_pending`의 기본값은 false로 유지합니다.
- 계정 이메일·인증 시각·재설정 수신처는 [병원 계정 생성 파일](../database/migrations/0001_01_01_000006_create_account_hospitals_table.php)에 포함합니다. [이메일 인증 기록](../database/migrations/2026_09_10_100000_create_hospital_account_email_verifications_table.php)은 별도 테이블입니다. 이전 휴대폰 데이터는 과거 이력 참조를 위해 보존하지만 신규 인증에 사용하지 않습니다.
- 권한과 기본 역할 매핑은 `AccessPermissions`·`AccessRoles`를 읽는 `AuthorizationSeeder`에서 관리합니다. 권한 추가용 마이그레이션을 만들지 않습니다. 기존 환경에서 전체 Seeder를 실행하면 사용자 지정 권한이 덮어써질 수 있습니다.
- 이미 적용된 생성 파일을 수정해도 `migrate`가 해당 변경을 재실행하지는 않습니다. 기존 DB는 실제 컬럼·인덱스·제약·권한을 먼저 대조하고 필요한 변경만 적용합니다. 마이그레이션 이력만 고쳐 누락된 스키마를 적용된 것처럼 처리하면 안 됩니다.
- 통합으로 없어진 `add` 이력은 해당 변경의 실제 적용과 원본 생성 이력을 확인한 뒤에만 정리합니다. 생성 파일 이름이 바뀐 경우 이미 존재하는 테이블을 재생성하지 않도록 이력 이름도 함께 맞춥니다. `rollback`, `migrate:fresh`, 전체 Seeder로 이력을 맞추지 않습니다.

인증 이메일 없는 기존 계정을 임의로 인증 완료 처리하거나 기존 재설정 링크를 마이그레이션 정리 과정에서 재활성화하지 않습니다. 구 휴대폰 인증에서 전환하는 별도 환경은 가입·재설정 요청과 워커를 통제하고 남아 있는 링크·인증 증표의 폐기 범위를 먼저 결정합니다. 스키마가 준비되기 전에 새 워커부터 실행하지 않습니다.

배포 기록에는 실제 실행한 명령, 적용 migration, 파일 이전 범위, 실발송 여부를 남깁니다.
과거 문서의 테스트 개수·성공 보고를 이번 배포 확인 결과로 재사용하지 않습니다.

## 프론트 개발자에게 API 개발 환경 제공

### 권장 구성

장기 협업은 개인 PC와 분리된 공용 개발 서버를 사용한다. 프론트 개발자는 웹·RN만 로컬에서 실행하고 개발 API에 연결한다.

```text
프론트 개발자 PC / 테스트 기기
  ├─ 로컬 웹 → Next API rewrite ─┐
  └─ React Native → Bearer 인증 ├─ HTTPS 개발 API
                               └─ Laravel + 개발 MySQL·Redis·파일 저장소
```

| 방식                 | 사용할 때                        | 제약                                                     |
| -------------------- | -------------------------------- | -------------------------------------------------------- |
| 별도 Linux 개발 서버 | 지속적인 협업과 실제 기기 테스트 | 서버 비용·배포·백업·계정 관리 필요                       |
| 개인 PC + 사설 VPN   | 개발 서버 준비 전 임시 연결      | PC·WSL·API가 켜져 있어야 하며 작업·재부팅 영향 발생      |
| 같은 사무실 내부망   | 짧은 공동 작업                   | 방화벽·WSL 네트워크 연결 필요, 사무실 밖에서는 접근 불가 |

공용 개발 서버는 인터넷 전체 공개를 기본값으로 두지 않는다. VPN 또는 허용된 접근 경로로 제한한다. VPN에 연결했다고 모든 서비스 포트를 허용하지 않고 API·필요한 WebSocket만 허용한다.

### 개발 서버에 필요한 구성

1. PHP 8.3과 프로젝트 확장, Composer, Nginx/PHP-FPM 또는 동등한 Laravel 실행 환경을 준비한다. 웹 루트는 저장소 전체가 아닌 `public/`이다.
2. 개발 전용 MySQL DB·Redis·파일 저장소·APP_KEY를 준비한다. 개인 작업 DB와 운영 DB를 그대로 공유하지 않는다.
3. 잠금 파일로 의존성을 설치한다. 기존 DB가 있으면 생성 migration 수정분이 자동 반영되지 않으므로 실제 스키마를 먼저 대조한다.
4. HTTPS 개발 주소를 정하고 `APP_URL`을 같은 주소로 맞춘다. 링크·서명 미디어가 개발자 PC와 기기에서 접근 가능해야 한다.
5. 합성 데이터와 개발자별 테스트 계정을 제공한다. 직원·병원·사용자 계정을 하나의 관리자 계정으로 대체하지 않는다.
6. 필요 기능에 맞춰 Horizon·Reverb·Scheduler를 프로세스 관리자로 실행한다. 개발 DB와 전용 큐인지 확인하고, 메일은 테스트 수신함·SMS/Push는 비활성 또는 승인된 테스트 대상만 사용한다.
7. API 주소, 테스트 계정, API 명세, 최신 배포 커밋, 배포·데이터 초기화 공지 방법을 프론트 개발자에게 전달한다. 환경 파일과 운영 자격 증명은 전달하지 않는다.

공유 개발 서버의 장기 실행에는 `artisan serve` 대신 관리되는 웹 서버 구성을 사용한다. `APP_DEBUG=false`를 적용하고 DB·Redis·메일 수신함·내부 운영 도구 접근을 별도로 제한한다. [Laravel 배포 문서](https://laravel.com/docs/12.x/deployment)를 참고한다.

### 웹 연결에서 주의할 프로젝트 조건

웹 프론트의 `.env.local`에는 Next 서버가 연결할 API 주소를 지정한다. 아래 도메인은 예시이며 실제 주소로 바꾼다.

```dotenv
API_URL=https://api-dev.example.com
```

브라우저는 계속 동일 출처 `/api/v1/{actor}`를 호출한다. 미디어 공개 주소가 필요한 직원 웹은 `NEXT_PUBLIC_API_URL`도 실제 접근 가능한 API 주소로 맞춘다. 웹 설정 변경 후 Next 서버를 재시작한다.

**현재 `StartActorWebSession`은 local/testing 이외 환경에서 HTTP Origin을 거절한다.** `APP_ENV=staging`인 API에 단순히 `http://localhost:3000`을 허용 목록에 추가해도 로그인할 수 없다.

- 공용 개발 API가 staging이면 프론트 개발 웹도 신뢰 가능한 HTTPS로 실행하거나 HTTPS 개발 웹을 함께 배포한다.
- 실제 브라우저 주소를 `STAFF_WEB_ORIGINS`, `HOSPITAL_WEB_ORIGINS`, `USER_WEB_ORIGINS`에 정확한 스킴·호스트·포트로 등록한다. API 주소를 브라우저 Origin 대신 넣지 않는다.
- 예를 들어 직원 웹 주소가 `https://localhost:3000`이면 `STAFF_WEB_ORIGINS=https://localhost:3000`으로 설정한다. 브라우저가 해당 개발 인증서를 신뢰해야 한다.
- 프록시의 Origin·Set-Cookie 전달, HTTPS 판별과 신뢰 프록시 범위를 확인한다. 토큰 검증이나 CSRF를 끄는 방식으로 연결 문제를 해결하지 않는다.
- 초대·재설정 링크는 수신자가 접근할 실제 프론트 주소로 설정한다. 개발자마다 다른 주소를 쓰면 고정된 메일 링크가 누구의 환경을 가리킬지도 합의한다.

### React Native 연결

RN 앱은 Next rewrite 없이 HTTPS API를 직접 호출하고 사용자 Bearer 토큰을 사용한다. 웹 전용 `X-Beaulab-Client: web`은 보내지 않는다. 기기의 localhost는 개발 PC가 아니며, VPN 방식이면 테스트 기기도 해당 네트워크에서 API 주소에 접근할 수 있어야 한다.

API 호출 성공 후 이미지·업로드·서명 URL·푸시를 각각 확인한다. 채팅은 Reverb의 WSS 주소·허용 Origin·인증 경로를 별도로 설정하고 확인한다. HTTP API 정상 응답만으로 실시간 연결까지 완료된 것은 아니다.

### 현재 개인 PC를 임시로 공유할 경우

2026-09-22 확인 시 WSL의 API는 `127.0.0.1:8000`에서 실행 중이었다. 다른 PC가 이 loopback 주소로 직접 접근할 수는 없다.

사설 VPN을 쓰려면 양쪽 기기를 등록하고, API가 실행되는 WSL 또는 그 앞의 프록시에 도달하도록 구성한다. VPN을 Windows에 설치한 것만으로 WSL 서비스가 자동 공유된다고 가정하지 않는다. [Tailscale 시작 안내](https://tailscale.com/kb/1017/install), [Microsoft WSL 네트워크 안내](https://learn.microsoft.com/en-us/windows/wsl/networking)를 참고한다.

내부망에서 임시 테스트 서버를 실행하는 명령 예시는 다음과 같다. 기존 API 프로세스·포트 사용을 확인한 뒤 선택해서 실행한다.

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

이 명령은 수신 주소만 바꾸며 WSL NAT·Windows/Hyper-V 방화벽·포트 전달을 자동 설정하지 않는다. 0.0.0.0은 접속 URL이 아니다. 허용된 개발자만 API 포트에 접근하도록 제한하고 실제 접속 가능한 호스트 주소를 전달한다. 현재 Redis가 0.0.0.0:6379에 바인딩된 것도 확인했으므로, PC 공유 시 Redis 포트까지 열리지 않도록 확인한다. 이것만으로 Redis가 인터넷에 공개됐다는 의미는 아니다.

이 문서는 구성 안내이며 실제 VPN 설치, 포트 개방, 서버 배포, 환경변수 변경을 수행한 기록은 아니다.
