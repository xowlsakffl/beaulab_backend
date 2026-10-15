# 로컬 실행과 검증

## 환경 기준

백엔드는 PHP 8.3 이상·Composer 2·MySQL 8·Redis를 사용합니다. 필요한 확장은 [composer.json](../../composer.json)과 `composer check-platform-reqs`로 확인합니다. 프론트 실행은 [프론트 개발 문서](https://github.com/beaulab/beaulab_new_frontend/blob/master/docs/development-verification.md)를 따릅니다.

OS별 패키지 설치 명령은 배포판·패키지 저장소에 따라 달라집니다. 특정 개발자 PC의 절대 경로나 서비스 이름을 프로젝트의 필수 조건으로 고정하지 않습니다.

## 기존 환경에서 시작

1. 현재 브랜치·변경 파일과 기존 실행 프로세스를 확인합니다.
2. DB 호스트·DB 이름, DB_URL, Redis 연결·prefix, 파일 디스크, APP_KEY 보유 여부를 확인합니다. 비밀값을 출력하지 않습니다.
3. 필요한 의존성·확장만 확인합니다.
4. API 서버만 시작합니다. 큐·스케줄러·Reverb는 필요한 범위와 부작용을 확인한 후 별도로 시작합니다.

```bash
composer check-platform-reqs
php artisan serve --host=127.0.0.1 --port=8000
```

포트가 사용 중이면 기존 서버를 임의 종료하지 않고 다른 포트를 사용합니다. 상태 확인 경로는 `/up`입니다.

## 새 환경 준비

`.env.example`은 변수 구조의 기준입니다. `.env.frontend.example`은 소규모 격리 시드용 예시이며 인력 채용 여부와 관계없이 필요할 때 선택하는 환경입니다.

새 DB·전용 사용자를 준비하고 로컬 파일·Redis를 분리합니다. 기존 `.env`를 덮어쓰거나 기존 APP_KEY를 재생성하지 않습니다. APP_KEY는 암호화 데이터·서명 링크 복구에 필요합니다.

대상과 변경 범위를 승인받은 뒤에만 마이그레이션·초기 데이터를 적용합니다. 적용된 DB에서 생성 마이그레이션을 고쳤다면 일반 `migrate`만으로 기존 테이블이 갱신되지 않습니다.

`composer setup`은 의존성 설치뿐 아니라 key:generate와 migrate --force까지 실행합니다. 기존 환경의 단순 실행 명령으로 사용하지 않습니다.

## 웹 연결

직원 3000, 사용자 3001, 병의원 3002가 기본 개발 포트입니다. Next의 서버용 API_URL과 미디어 공개 URL, API APP_URL, 허용 Origin을 맞춥니다. localhost와 127.0.0.1은 브라우저 출처가 같지 않습니다.

로컬은 HTTP가 가능하지만 local/testing 이외 웹 세션은 HTTPS Origin 조건을 확인해야 합니다. Reverb 공개 키는 프론트에 전달할 수 있지만 APP_SECRET과 서버 비밀키는 전달하지 않습니다.

## 테스트 격리

[phpunit.xml](../../phpunit.xml)은 `APP_ENV=testing`, `DB_DATABASE=beaulab_testing`, DB_URL 해제, Redis DB 15·테스트 prefix, array 캐시·메일, log 방송, 푸시 비활성화를 강제합니다.

이 설정만 믿고 실행하지 않습니다. 실제 연결이 테스트 전용인지, DB 계정의 권한이 분리됐는지 확인해야 합니다. 테스트 DB는 삭제·재생성될 수 있으므로 업무 DB와 공유하지 않습니다.

```bash
vendor/bin/pint --test
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
```

Feature 테스트는 DB를 변경할 수 있습니다. 위 명령은 실행 지침이지 이 문서를 읽는 작업에 대한 자동 승인 아닙니다. 별도 `tests/Performance`는 기본 Unit·Feature 스위트에 포함되지 않으므로 목적·데이터 규모·격리를 확인한 뒤 직접 실행합니다.

## 문제가 있을 때

| 현상               | 먼저 확인                                                   |
| ------------------ | ----------------------------------------------------------- |
| 로그인 419·403     | Origin, actor, CSRF, 쿠키, 계정 상태·permission             |
| 새로고침 세션 실패 | 실제 HTTP 오류·서버 traceId, 동시 세션 요청, 프록시 쿠키    |
| 이미지 실패        | APP_URL·서명 만료·디스크·원본 파일·접근 권한                |
| 알림·상담 지연     | 전용 큐·발행 대기·Reverb·채널 인증                          |
| 빈 통계            | 수집 대상·기간·권한·샘플 데이터 범위                        |
| 디스크 부족        | DB·업로드와 재생성 가능한 캐시를 구별하고 실행 중 쓰기 확인 |

기존 데이터를 지우는 방식으로 로그인·스키마·이미지 문제를 우회하지 않습니다.
