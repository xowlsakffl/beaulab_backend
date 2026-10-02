# 백엔드 개발 시작

현재 구현을 찾고 로컬에서 안전하게 실행하기 위한 안내입니다. 문서에 없는 필드와 API는 소스가 기준이며, 과거 작업 완료 보고를 현재 검증 결과로 취급하지 않습니다.

## 프론트 개발자 빠른 시작

자기 PC에서 API만 실행할 경우 [설치·실행 절차](local-setup.md)를 순서대로 진행하고 [개발 데이터](development-data.md)의 계정과 테스트 상황을 사용한다. 전용 환경 예시는 [.env.frontend.example](../.env.frontend.example)이며 기본 전체 Seeder와 구분한다.

## 읽는 순서

| 작업                                   | 문서                                |
| -------------------------------------- | ----------------------------------- |
| 처음 합류, 새 API 개발                 | [구조와 개발 기준](architecture.md) |
| 프론트 연동, 로그인, 권한, 병의원 계정   | [API·인증](api.md)                  |
| 검수·노출, 이벤트, 신고, 운영 히스토리 | [도메인 규칙](domain-rules.md)      |
| 충전·소진·환불·현황                    | [충전금](hospital-wallet.md)        |
| 채팅, 알림, SMS                        | [메시징](messaging.md)              |
| 배포, 장애 확인, 큐, 파일 이전         | [운영 절차](operations.md)          |

병의원관리자 챗봇·상담 기능은 [PRD](hospital-chatbot-prd.md)와 [작업지시서](hospital-chatbot-work-order.md)를 함께 확인합니다. 연동 계약(Q&A·폴더·상담·메시지·내보내기·실시간·로그)은 [API 문서](api.md), 전체 구조와 추가된 테이블은 [구조 문서](architecture.md#병의원-챗봇-구조)에 있습니다.

[DB 관계도](schema.dbml)는 탐색용입니다. 실제 제약과 컬럼은 [마이그레이션](../database/migrations)과 각 Model을 확인하세요.

## Git 협업

브랜치·커밋·PR·충돌 해결은 [Git 협업 규칙](git-workflow.md)을 따른다. 개발자와 AI 작업 도구에 동일하게 적용한다.

## 필요한 환경

- PHP 8.3 이상, Composer. 확장과 라이브러리는 [composer.json](../composer.json)이 기준입니다.
- MySQL 8 계열, Redis. CI도 MySQL·Redis를 사용하므로 SQLite만으로 동작을 검증하지 않습니다.
- API 프로세스 외에 메일·알림 처리용 큐 워커가 필요합니다. Reverb와 Scheduler는 해당 기능을 개발할 때 별도로 실행합니다.
- 프론트는 별도 저장소입니다. 로컬 포트는 staff 3000, user 3001, hospital 3002, beauty 3003 기준입니다.

## 로컬 설정

1. `composer install`로 잠금 파일의 의존성을 설치합니다.
2. 신규 환경에서만 [환경 예시](../.env.example)를 `.env`로 복사합니다. 기존 설정을 덮어쓰지 않습니다.
3. 프로젝트 전용 MySQL DB와 Redis DB/키 접두사를 지정합니다. 운영·공유 개발 DB를 연결하지 않습니다.
4. 새 환경의 빈 `APP_KEY`만 생성합니다. 기존 키를 변경하면 암호화 계좌·인증 데이터 등을 읽지 못할 수 있습니다.
5. 연결 대상을 확인하고 필요한 마이그레이션을 적용합니다. 샘플 데이터는 필요할 때 개발용 Seeder의 실행 범위를 먼저 확인합니다.

`.env.example`에는 아직 Laravel 기본 `DB_CONNECTION=sqlite`, `MAIL_MAILER=log` 등이 남아 있습니다.
그대로 실행 가능한 완성 설정이 아니며, 아래 항목을 개발 환경에 맞게 바꿔야 합니다.

| 설정                              | 확인 사항                                                         |
| --------------------------------- | ----------------------------------------------------------------- |
| `APP_URL`                         | 실제 API 주소. 서명 파일 URL을 생성하므로 호스트·스킴이 맞아야 함 |
| `DB_*`                            | MySQL 연결과 전용 개발 DB                                         |
| `REDIS_*`, `QUEUE_CONNECTION`     | 전용 Redis 연결, 큐는 Redis                                       |
| `*_WEB_ORIGINS`                   | 브라우저 Origin 허용 목록. 운영에는 기본 허용 주소가 없음         |
| `HOSPITAL_ACCOUNT_INVITATION_URL` | 로컬 기본 `http://localhost:3002/account/create`                  |
| `PASSWORD_RESET_HOSPITAL_URL`     | 로컬 기본 `http://localhost:3002/password/reset`                  |
| `MAIL_*`                          | 로컬 SMTP 수신함 또는 승인된 메일 환경                            |
| `SMS_ENABLED`, `PUSH_ENABLED`     | 실제 발송을 확인하기 전에는 false 유지                            |
| `MEDIA_*`                         | 공개/비공개 디스크를 구분하고 저장 경로 권한 확인                 |

병의원 인증 메일은 인증번호와 링크의 로그 유출을 막기 위해 `log` transport를 거부합니다.
로컬에서는 Mailpit 같은 SMTP 수신함을 사용할 수 있습니다. 예를 들어 SMTP 1025, 수신함 UI 8025로 실행했다면 해당 주소를 설정합니다. 이는 프로젝트가 자동 설치·실행하는 서비스가 아닙니다.
그 밖의 Actor 재설정 URL도 배포한 웹 주소에 맞춰 확인합니다.

## 실행

기존 서버의 포트와 연결 환경을 확인한 뒤 API를 실행합니다.

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

메일·알림이 필요한 경우 별도 터미널에서 Horizon을 실행합니다.

```sh
php artisan horizon
```

Horizon 실행만으로 대기 중인 작업이 실제 발송될 수 있습니다. 안전한 로컬 메일 설정과 전용 Redis인지 먼저 확인하세요.
Reverb는 실시간 기능에, Scheduler는 예약·복구 작업에 필요합니다. 실행 및 부작용은 [운영 절차](operations.md)를 따릅니다.

`composer setup`은 설치만 하는 명령이 아닙니다. 키 생성과 `migrate --force`도 포함하므로 기존 환경에 무심코 실행하지 않습니다.
`migrate:fresh`, 전체 Seeder, 큐 재시도 역시 시작 안내의 필수 단계가 아닙니다.

## 프론트 협업용 API 제공

개인 PC 임시 공유와 공용 개발 서버 구성은 [프론트 개발자에게 API 개발 환경 제공](operations.md#프론트-개발자에게-api-개발-환경-제공)을 확인한다. HTTPS 웹 Origin, 서명 미디어 주소, 메일·큐 설정까지 함께 맞춘다.

## 문서 관리

- 폴더별 전체 API·필드·enum을 문서에 복제하지 않습니다. 바뀌기 쉬운 값은 Request·Model·config로 연결합니다.
- 계약, 예외적인 업무 규칙, 실패 복구 절차가 바뀌면 해당 주제 문서를 함께 수정합니다.
- 임시 점검 결과와 작업 보고는 커밋·PR에 남깁니다. 같은 내용을 별도 설계서·완료 보고서로 늘리지 않습니다.
