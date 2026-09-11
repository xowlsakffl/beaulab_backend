# 뷰랩 백엔드

성형·뷰티 플랫폼 뷰랩의 Laravel API 서버입니다. 기존 앱·서버와 운영 프로세스를 분석한 뒤 사용자 역할, 도메인, 데이터 구조와 API를 신규 개발 수준으로 재설계했습니다.

백엔드는 내부 운영자, 병원 파트너, 뷰티 파트너, 일반 사용자의 API 경계를 분리하고 병원·의료진·이벤트·콘텐츠·지갑·채팅·알림·SMS 등의 비즈니스 규칙을 관리합니다. 관리자 프론트엔드는 별도 [뷰랩 프론트엔드 저장소](https://github.com/xowlsakffl/beaulab_frontend)에서 관리합니다.

## 담당 범위

- 기존 시스템, 관리자 동선, API와 데이터 구조 분석
- 요구사항·기능 흐름 정의와 ERD, 도메인·상태, API 명세 작성
- Actor별 인증·권한 경계와 도메인 중심 애플리케이션 구조 설계
- 백엔드 API, 관리자 기능, 외부 발송과 실시간 통신 구현
- Redis Queue, Horizon, Scheduler, 내부 운영 도구 구성
- 배포 이후 로그·처리 상태 확인과 운영 이슈 개선

## 개발 배경

기존 시스템은 일반 사용자, 병원·뷰티 파트너와 내부 운영자의 기능이 한 구조에 섞여 있었습니다. 일부 접근 제어가 서버 권한 검증보다 메뉴 노출 여부에 의존했고, 여러 개발자를 거치며 API 응답, 예외 처리, 데이터 구조와 코드 작성 방식도 일관되지 않았습니다.

신규 기능을 계속 덧붙이는 방식으로는 변경 범위와 운영 위험이 커진다고 판단했습니다. 기존 기능과 운영 흐름을 먼저 문서화한 뒤 다음 기준으로 구조를 다시 설계했습니다.

- 사용자 역할과 실제 업무 도메인을 서로 다른 경계로 분리
- 권한, 트랜잭션과 비즈니스 규칙을 HTTP 계층에서 분리
- 중복 요청과 동시 수정이 발생해도 상태와 원장이 깨지지 않는 구조 적용
- 외부 발송, 실시간 이벤트와 유지보수 작업을 독립적으로 운영
- API 응답, 예외, 목록 조회와 운영 기록의 공통 기준 수립

## 현재 구현 범위

| 영역 | 주요 기능 |
| --- | --- |
| 계정·권한 | Staff, Hospital, Beauty, User 인증 경계, Sanctum ability, 역할·권한 검증 |
| 파트너 운영 | 병의원, 입점 신청, 의료진, 뷰티 업체, 뷰티 전문가 관리 |
| 콘텐츠 운영 | 병원 이벤트·광고, 영상, 토크·댓글, 성형·시술 후기, 병의원 평가 |
| 운영 데이터 | 이벤트 신청 DB, 리얼모델 신청 DB, 공지사항, FAQ, 카테고리, 해시태그 |
| 신고·이력 | 신고 콘텐츠 검수·조치, 관리자 메모, 상태 변경과 운영 이력 |
| 정산·발송 | 병원 지갑·환불·원장, SMS, 메일, Push 알림 |
| 커뮤니케이션 | 사용자 1:1 채팅, 읽음 처리, 알림 설정, Reverb 실시간 이벤트 |

Staff API와 관리자 운영 기능이 현재 구현의 중심입니다. Hospital, Beauty, User는 별도 인증 진입점과 각 Actor가 사용하는 API만 제공하며, 아직 구현되지 않은 서비스 기능을 현재 범위에 포함하지 않습니다.

## 아키텍처

### Actor와 Domain 분리

Actor는 API를 사용하는 주체이고 Domain은 업무 규칙의 소유자입니다.

```text
/api/v1/staff     내부 운영자 API
/api/v1/hospital  병원 파트너 API
/api/v1/beauty    뷰티 파트너 API
/api/v1/user      일반 사용자 API
```

```text
HTTP Request
  -> app/Modules/{Actor}/Controller
  -> app/Domains/{Domain}/Action
  -> Query / Model / Policy
  -> DTO
  -> ApiResponse
```

- `app/Modules/*`: Actor별 route, controller, request
- `app/Domains/*`: 도메인별 action, query, DTO, model, policy
- `app/Common/*`: 공통 응답, 예외, 권한 상수와 middleware

Controller는 요청 검증 결과를 Action에 전달하고 응답을 반환하는 역할에 집중합니다. 권한 검증, 상태 전이, 트랜잭션과 외부 연동 규칙은 해당 도메인이 소유합니다.

### 주요 도메인

```text
AccountStaff / AccountHospital / AccountBeauty / AccountUser
Hospital / HospitalEntry / HospitalDoctor / HospitalFeature
HospitalEvent / HospitalEventAd / HospitalVideo
HospitalReview / HospitalEvaluation / Talk
HospitalWallet / Chat / Notice / Faq
Beauty / BeautyEvent / BeautyExpert / Common
```

## 핵심 구현

### 1. 인증과 권한

- `/api/v1/{actor}` 단위로 API 진입점 분리
- Sanctum 토큰 ability로 Actor 불일치 요청 차단
- Staff·Hospital·Beauty 역할과 권한은 Spatie Permission으로 관리
- 메뉴 노출과 관계없이 서버에서 실제 권한 검증
- 로그인, 비밀번호 재설정, 신고와 SMS 발송에 요청 제한 적용

### 2. 데이터 정합성과 중복 요청 방지

동시성은 스레드를 직접 제어하는 방식이 아니라 같은 데이터가 동시에 변경돼도 잔액, 메시지와 처리 상태가 깨지지 않도록 구현했습니다.

- 병원 지갑 환불: 트랜잭션 안에서 지갑 row를 잠그고 잔액 검사·차감·환불 예약 처리
- 환불 요청: `idempotency_key`와 DB unique 제약으로 동일 요청 중복 처리 방지
- 1:1 채팅방: 참여자 조합 `match_key` unique 제약으로 중복 채팅방 생성 방지
- 채팅 메시지: `client_message_id`로 모바일 네트워크 재전송 멱등 처리
- 원장: 잔액 변경과 operation·transaction 기록을 같은 트랜잭션으로 처리

### 3. 비동기 작업과 실패 재처리

SMS, 메일, 채팅, 알림과 유지보수 작업은 Redis Queue와 Horizon으로 분리했습니다.

```text
critical / mail / sms / chat / notifications / default / maintenance
```

- SMS pending row를 선점한 후 큐에 등록
- Worker가 row lock을 획득하고 `PROCESSING` 상태로 전환한 뒤 외부 API 호출
- 작업 성격에 따라 worker 수, 재시도 횟수와 timeout 분리
- 외부 API 최종 실패 상태와 원인을 기록해 운영 중 추적 가능
- Scheduler와 Schedule Monitor로 정기 작업 실행·결과 관리

### 4. 실시간 채팅과 안정적인 목록 조회

- Reverb private channel과 서버 채널 인증 적용
- 채팅방 참여자만 `chat.{id}` 채널 구독 가능
- 메시지는 `before_id`, `after_id` 기반 cursor 방식으로 조회
- 값이 중복될 수 있는 정렬 컬럼 뒤에 ID 정렬을 추가해 목록 순서 고정
- 메시지 전송, 읽음과 알림 이벤트를 큐·브로드캐스트 흐름으로 분리

### 5. 공통 API와 운영 추적

- 성공·실패 응답을 공통 `ApiResponse` 형식으로 통일
- 요청마다 `traceId`를 발급해 응답과 로그를 연결
- 도메인 상태 변경과 관리자 조치를 operation history와 activity log로 기록
- Scramble API Docs, Horizon, Telescope를 내부 도구 허브에서 확인
- 내부 도구는 Staff 세션, 허용 계정·IP와 Gate 정책으로 접근 제한

## 기술 스택

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Queue%20%26%20Cache-DC382D?style=flat-square&logo=redis&logoColor=white)

- PHP 8.3, Laravel 12, Eloquent ORM
- MySQL/MariaDB, SQLite(local)
- Redis, Horizon, Scheduler, Schedule Monitor
- Sanctum, Spatie Permission, Activitylog, Query Builder
- Reverb, Laravel Echo private channel
- FCM, APNs, SMS provider, Mailgun
- S3-compatible object storage
- Scramble, Telescope, PHPUnit 11, Laravel Pint

## 프로젝트 구조

```text
beaulab/
├── app/
│   ├── Common/                 # 공통 응답, 예외, 권한, middleware
│   ├── Domains/                # 도메인별 Action, Query, DTO, Model, Policy
│   ├── Modules/                # Actor별 HTTP 진입점
│   │   ├── Staff/
│   │   ├── Hospital/
│   │   ├── Beauty/
│   │   └── User/
│   └── Providers/              # Rate limit, Horizon, Telescope 등
├── config/                     # 인증, 큐, 실시간, 외부 연동 설정
├── database/                   # migration, seeder, factory
├── resources/views/tools/      # 내부 운영 도구
├── routes/                     # API, channel, scheduler, web route
└── tests/                      # 도메인 단위 테스트
```

## 실행 방법

PHP 8.3 이상, Composer와 Redis가 필요합니다. `.env.example`의 로컬 기본 DB는 SQLite입니다.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan serve
```

별도 프로세스:

```bash
php artisan horizon
php artisan reverb:start
php artisan schedule:work
```

검증:

```bash
composer test
./vendor/bin/pint --test
php artisan route:list --path=api/v1
```

## 주요 문서

아래는 문서 주제 목록이며, 상세 본문은 현재 저장소에 포함하지 않습니다.

- 문서 목록
- 아키텍처
- API 응답·페이지네이션 규칙
- 인증·권한
- 도메인·상태 정의
- 성능·인덱스·쿼리
- 병원 지갑 정합성
- 채팅
- SMS
- 알림
- Queue
- Scheduler
- 로깅
- 내부 운영 도구

## 관련 저장소

- 백엔드: [xowlsakffl/beaulab_backend](https://github.com/xowlsakffl/beaulab_backend)
- 프론트엔드: [xowlsakffl/beaulab_frontend](https://github.com/xowlsakffl/beaulab_frontend)
