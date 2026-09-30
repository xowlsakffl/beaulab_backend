# 프론트 개발자용 로컬 API 설치·실행

목표는 프론트 개발자가 자기 PC에서 Laravel API와 개발 데이터를 실행하고 웹·React Native를 연동하는 것이다. AWS 서버나 운영 DB는 필요하지 않다.

기준 환경은 **Ubuntu 24.04 또는 Windows의 WSL2 Ubuntu 24.04**다. 명령은 Ubuntu 터미널에서 실행한다. macOS 사용자는 PHP 8.3·MySQL 8·Redis·Composer를 준비한 뒤 저장소 설정 단계부터 동일하게 진행할 수 있지만, 아래 apt 설치 명령은 사용할 수 없다. macOS 설치와 iOS 빌드는 이번에 검증하지 않았다. macOS에서 수행한 기록은 [macOS 로컬 API 환경 구축 기록](local-setup-macos.md)에 있다.

## 1. 처음 한 번 설치

이미 환경이 준비되어 있으면 버전·서비스 상태만 확인한다. Windows는 먼저 WSL2 Ubuntu 24.04를 준비한다. 소스와 의존성은 WSL의 홈 디렉터리에 두고 Windows PHP와 혼용하지 않는다.

```bash
sudo apt update
sudo apt install git unzip curl openssl composer mysql-server redis-server php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
sudo service mysql start
sudo service redis-server start
php -v
composer --version
mysql --version
redis-cli ping
```

PHP는 8.3 이상, MySQL은 8 계열, Composer는 2 계열이어야 한다. Redis는 `PONG`을 반환해야 한다. 패키지 설치가 불가능한 다른 Ubuntu 버전에 위 명령을 그대로 적용하지 않는다. [Ubuntu PHP 설치 안내](https://ubuntu.com/server/docs/how-to/web-services/install-php/), [Composer 설치 안내](https://getcomposer.org/download/)를 참고한다.

## 2. 새 저장소와 환경 파일

```bash
mkdir -p ~/projects
cd ~/projects
git clone https://github.com/beaulab/beaulab_new_dev.git beaulab
cd beaulab
composer install
composer check-platform-reqs
```

GitHub 접근 권한이 필요하다. 기존 checkout을 사용한다면 위 clone을 반복하지 말고 [Git 규칙](git-workflow.md)에 따라 변경을 보존한다.

**`.env`가 없는 새 checkout에서만** [프론트 개발용 환경 예시](../.env.frontend.example)를 복사한다. 이미 `.env`가 있으면 기존 APP_KEY·DB 연결을 덮지 않고 담당자와 용도를 확인한다.

```bash
cp --no-clobber .env.frontend.example .env
chmod 600 .env
```

파일에 DB_PASSWORD와 FRONTEND_SEED_PASSWORD를 직접 입력한다. 비밀번호는 각자 생성하며 아래 명령을 각각 실행해 다른 값을 사용한다.

```bash
openssl rand -hex 16
```

| 설정                     | 입력 내용                                                    |
| ------------------------ | ------------------------------------------------------------ |
| `DB_DATABASE`            | 기본 `beaulab_frontend_local`. 전용 DB만 사용                |
| `DB_USERNAME`            | 기본 `beaulab_frontend`                                      |
| `DB_PASSWORD`            | 다음 단계에서 만들 DB 사용자의 비밀번호                      |
| `FRONTEND_SEED_PASSWORD` | 이번 PC의 개발 로그인 계정에 사용할 별도 비밀번호, 최소 12자 |
| `FRONTEND_SEED_ENABLED`  | 최초 데이터 생성 때만 `true`                                 |

예시 설정은 메일을 메모리에서 처리해 외부로 보내지 않고 SMS·Push도 비활성화한다. Composer에 포함된 Predis 클라이언트와 Redis DB 10·11, 전용 prefix를 사용한다. 이미 이 번호·prefix를 사용하는 앱이 있으면 겹치지 않게 변경한다. 테스트용 Redis DB 15와도 구분한다.

DB_URL·REDIS_URL 등 외부 환경변수가 연결값을 덮어쓰지 않는지 확인한다. 기존 운영 `.env`를 복사하지 않는다. `composer setup`은 키·마이그레이션까지 바꾸므로 이 절차 대신 실행하지 않는다.

## 3. 전용 DB 생성

로컬 MySQL 관리자 접속 후 다음 SQL을 실행한다. 아래 `본인이_생성한_DB_비밀번호`는 DB_PASSWORD에 넣은 값으로 바꾼다. 같은 이름의 DB·계정이 이미 있으면 삭제하지 말고 용도를 먼저 확인한다.

```bash
sudo env MYSQL_HISTFILE=/dev/null mysql
```

```sql
CREATE DATABASE beaulab_frontend_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'beaulab_frontend'@'127.0.0.1' IDENTIFIED BY '본인이_생성한_DB_비밀번호';
GRANT ALL PRIVILEGES ON beaulab_frontend_local.* TO 'beaulab_frontend'@'127.0.0.1';
EXIT;
```

애플리케이션은 root 대신 이 전용 사용자를 쓴다. DB가 새로 준비됐고 `.env`의 APP_KEY가 비어 있는 것을 확인한 뒤 실행한다.

```bash
php artisan key:generate
php artisan config:clear
php artisan migrate:status
php artisan migrate
php artisan db:seed --class=FrontendDevelopmentSeeder
php artisan storage:link
```

새 DB에서 migrate:status가 아직 migration 테이블이 없다고 표시하는 것은 정상이다. migrate가 성공한 뒤 Seeder를 실행한다. **`migrate:fresh`나 기본 `db:seed`는 이 안내의 실행 명령이 아니다.**

Seeder는 local 환경·명시적 활성화·전용 DB 이름·로컬 연결·빈 업무 테이블을 검사한다. 이미 데이터가 있으면 변경하지 않고 중단한다. 자동으로 기본 DatabaseSeeder에 연결하지 않았으므로 클래스를 명시해야 한다.

성공하면 생성한 로그인 입력값과 리소스 ID가 출력된다. 비밀번호는 출력하지 않는다. `.env`의 `FRONTEND_SEED_ENABLED=false`로 바꾸고 `php artisan config:clear`를 실행한다. 데이터 목록은 [개발 데이터 사용 안내](development-data.md)를 확인한다.

## 4. 매일 실행할 명령

MySQL·Redis가 실행 중인지 확인하고 API를 켠다.

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

다른 터미널에서 응답을 확인한다.

```bash
curl -i http://localhost:8000/up
```

HTTP 200은 API 프로세스의 기동 확인이다. DB·로그인·파일·큐까지 검증한 결과는 아니므로 아래 로그인 확인을 함께 수행한다.

프론트 저장소의 각 웹앱 `.env.local`:

```dotenv
API_URL=http://127.0.0.1:8000
```

직원 웹은 상대 미디어 URL을 사용할 때 `NEXT_PUBLIC_API_URL=http://localhost:8000`도 지정한다. 이후 필요한 웹을 실행한다.

```bash
pnpm --filter user-web dev
pnpm --filter staff-web dev
pnpm --filter hospital-web dev
```

각 명령은 별도 터미널에서 실행한다. `user-web`은 localhost:3001, 직원 웹은 localhost:3000/login, 병원 웹은 localhost:3002/login에서 확인한다. 한 앱 안에서는 localhost와 127.0.0.1을 섞지 않는다.

## 5. 메일·실시간 기능이 필요할 때

| 기능                   | 기본 상태                                       | 사용할 때                                                                |
| ---------------------- | ----------------------------------------------- | ------------------------------------------------------------------------ |
| 로그인·프로필·일반 API | API + MySQL + Redis로 사용                      | 개발 계정으로 확인                                                       |
| 이메일                 | `MAIL_MAILER=array`, 실제 전달·수신함 없음      | 로컬 Mailpit을 별도 설치하고 SMTP 주소·포트 확인 후 mailer를 smtp로 변경 |
| 채팅 실시간 이벤트     | `BROADCAST_CONNECTION=log`, WebSocket 전달 없음 | Reverb 비밀값 생성, broadcast를 reverb로 변경, Reverb·큐 실행            |
| SMS·푸시               | 비활성                                          | 실발송은 별도 승인된 테스트 환경에서만                                   |
| 예약·복구 작업         | 자동 실행하지 않음                              | 필요한 작업과 삭제·발송 영향을 확인한 뒤 Scheduler 사용                  |

Mailpit의 일반적인 로컬 SMTP/UI 포트는 1025/8025다. 설치는 [Mailpit 공식 안내](https://mailpit.axllent.org/docs/install/)를 따른다. 메일 수신함 UI를 외부에 공개하지 않는다. SMTP는 로컬 Mailpit에만 연결하며 병원 인증에서 금지하는 log mailer로 대체하지 않는다.

큐가 필요한 경우 전용 Redis·안전한 발송 설정을 확인하고 별도 터미널에서 실행한다.

```bash
php artisan horizon
```

채팅 실시간 개발은 `.env`의 REVERB_APP_SECRET에 로컬 비밀값을 넣고 BROADCAST_CONNECTION=reverb로 변경한 뒤 실행한다. 설정 변경 후 config:clear와 기존 워커 재시작도 필요하다.

```bash
php artisan reverb:start
```

RN의 연결 주소·Bearer 인증·채널 권한은 [메시징](messaging.md)을 따른다. 기본 데이터의 채팅 목록 조회와 실제 메시지 이벤트 수신은 별도 검증이다.

## 6. RN 에뮬레이터·실제 기기 연결

- 웹은 Next rewrite를 쓰지만 RN은 API 기본 주소로 직접 요청한다. `X-Beaulab-Client: web`을 보내지 않는다.
- 실제 휴대폰의 localhost는 휴대폰 자신이다. 개발 PC의 접근 가능한 주소와 API 수신·방화벽 설정이 필요하다.
- Android Emulator의 호스트 접근 주소도 WSL API에 자동 연결되는 것은 아니다. PC/WSL 포트 전달 경로를 확인한다.
- 내부망으로 실행할 때는 기존 프로세스·포트를 확인하고 `php artisan serve --host=0.0.0.0 --port=8000`을 사용한다. 접근 범위는 개발자 기기로 제한한다.
- `APP_URL`도 기기에서 접근 가능한 주소로 맞춰야 서명 이미지 링크가 열린다. API만 연결하고 미디어 주소를 localhost로 두지 않는다.
- Reverb를 사용할 때 WebSocket의 주소·수신 인터페이스도 별도로 설정한다. 릴리스 앱에 개발용 HTTP 허용 설정을 남기지 않는다.

WSL·사설망·HTTPS 개발 서버 연결은 [운영 절차](operations.md#프론트-개발자에게-api-개발-환경-제공)를 참고한다.

## 7. 문제 해결과 완료 기준

| 증상                          | 확인                                                         |
| ----------------------------- | ------------------------------------------------------------ |
| MySQL access denied           | .env DB 비밀번호, 사용자 host, 전용 DB 권한                  |
| Redis connection refused      | Redis 실행·포트·선택 DB·클라이언트 확장                      |
| Seeder not empty              | 이미 사용 중인 DB. 기존 데이터를 삭제하지 말고 보존          |
| Seeder 환경 오류              | APP_ENV=local, 전용 DB 이름, 활성화 값, 실제 연결 대상       |
| 로그인 419·403                | Origin·쿠키·계정 유형·권한. restricted 계정은 업무 권한 없음 |
| 이미지 404·서명 오류          | APP_URL, storage:link, 실제 파일·주소의 접근 가능 여부       |
| 메일이 안 옴                  | 기본 array는 수신함이 없음. SMTP·Mailpit·큐 확인             |
| 데이터를 바꿨는데 그대로 보임 | API 오류와 캐시·세션을 분리해 확인. Redis 전체 flush 금지    |

설치 완료 기준은 관리자·일반 사용자·병원 계정 로그인, 정상 사용자 채팅 조회, 빈 사용자 목록, 정지 사용자 로그인 거절, 직원 화면의 병원·이벤트·이미지 확인이다. React Native 자체는 아직 구현되지 않아 API 설치만으로 앱 실행까지 완료되지는 않는다.

2026-09-22 별도 MySQL·Redis에서 환경 예시를 사용해 마이그레이션·데이터 생성·재실행 거절을 확인했다. HTTP 요청으로 직원·병원 로그인, 사용자 토큰 로그인, 기존·빈 채팅 목록, 정지 계정 거절을 확인했고 기존 백엔드 테스트 37개도 통과했다. OS 최초 설치, 실제 브라우저 레이아웃·휴대폰 연결, SMTP·WebSocket 전달은 해당 개발자 환경에서 추가 확인한다.

## 비대면 상담 개발 적용

기존 로컬 개발 DB의 영문 표시값 보정은 `php artisan db:seed --class=KoreanDisplayDataSeeder`로 별도 실행합니다. 병의원·의료진·고객 DB·후기 등의 표시 필드만 변경하며 ID, 로그인 정보, 금액, 상태, 생성일과 운영 이력은 유지합니다. 변경 전·후 값은 `storage/app/private/maintenance-backups`에 비공개 파일로 저장됩니다. 개인정보가 포함될 수 있으므로 백업을 저장소에 추가하거나 외부에 공유하지 않습니다. 이미 한국어인 값은 보존하며 기본 시더에서는 자동 호출하지 않습니다.

기존 DB는 초기화하지 않습니다. 대상 환경과 백업을 확인한 뒤 신규 테이블 migration을 적용합니다.

```bash
php artisan migrate --path=database/migrations/2026_09_30_100000_create_remote_consultations_tables.php
```

권한은 기존 `AuthorizationSeeder` 정의에 포함되어 있습니다. `php artisan db:seed --class=AuthorizationSeeder`는 **역할별 기존 권한 전체를 코드 정의에 맞춰 동기화**하므로 수동 권한 구성이 있는 환경에서는 차이를 검토한 뒤 적용합니다. 적용 후 직원은 다시 로그인해 메뉴·승인 버튼을 확인합니다.

회원 신청 기능이 아직 없으므로 빈 목록은 정상입니다. 개발 DB에서만 필요할 때 `php artisan db:seed --class=RemoteConsultationDevelopmentSeeder`를 명시적으로 실행할 수 있습니다. 기존 활성 진료과목, 배정 조건을 충족하는 병원, `public/test.png`가 필요합니다. `[개발용] 상담회원 01~24` 이름으로 신청 12건·승인 12건을 생성하며, 성별·신청일·선택 항목·사진 슬롯·견적 답변 상태를 섞어 목록과 상세를 확인할 수 있습니다. 실제 승인 Action이나 외부 발송은 실행하지 않습니다. 기존 회원/병원 계정·권한은 변경하지 않습니다. 같은 개발용 이름이 있으면 신규 생성을 건너뛰되, 배정이 없는 신청 건은 배정을 보완하고 상담 완료 견적의 추천 이벤트가 비어 있으면 같은 병원의 기존 이벤트를 최대 3개 연결합니다. 따라서 직접 배정을 모두 삭제한 개발용 신청도 재실행 시 다시 배정될 수 있습니다. `DatabaseSeeder`에서는 자동 실행하지 않습니다.

신청 시 배정은 `RemoteConsultationAssignOnSubmissionAction`을 사용합니다. 시더는 기존 개발용 신청 상태 건 중 배정이 전혀 없는 건만 보정하고 기존 배정·답변은 유지합니다. 신규 건은 승인 전부터 선택 병원과 랜덤 병원으로 최대 6곳을 배정합니다. 후보 부족 예외는 기존 정책을 유지합니다.

일반회원 현황의 방문자 차트 샘플은 `php artisan db:seed --class=AccountUserStatisticsDevelopmentSeeder`로 생성합니다. local/testing에서만 실행되며, 최근 12개월부터 현재 시각까지의 가상 방문 세션을 추가합니다. 일별 30일·월별 12개월 차트와 방문수 요약을 확인할 수 있습니다. 고정된 샘플 ID를 사용하므로 재실행해도 같은 기록은 중복되지 않고, 시간이 지난 만큼의 샘플만 추가됩니다. 기존 회원과 실제 방문 기록은 수정하지 않습니다. 실제 트래픽 통계가 아니므로 기본 시더에서는 자동 실행하지 않습니다.

검증은 독립 MySQL·Redis의 `beaulab_testing`에서 진행합니다. `RemoteConsultationFlowTest`는 신청 단계 배정, 선택 2+랜덤 4, 부분 진료과목 일치, 승인 전후 배정 보존, 재승인 멱등성, 권한 구분, 견적 소속, 미발송 답변 숨김, 목록 정렬·필터와 서명 URL을 확인합니다. 기존/운영 DB에서 `migrate:fresh`를 실행하지 않습니다.
