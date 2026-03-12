# Queue 운영 가이드

이 문서는 Beaulab 프로젝트의 Queue, Redis, Horizon 구조와 운영 규칙을 정리한다.  
스케줄러(crontab / Laravel Scheduler / Schedule Monitor) 내용은 `scheduler.md`에서 관리한다.

## 1. 목적

1. 비동기 작업 처리 구조를 표준화한다.
2. 큐 레인별 책임을 분리해 병목을 줄인다.
3. 장애 시 실패 원인과 복구 절차를 빠르게 수행한다.

## 2. 현재 런타임 구조

현재 기본 큐 연결은 Redis다.

- `config/queue.php` 기본값: `redis`
- `.env` 기준: `QUEUE_CONNECTION=redis`

Horizon이 Redis 큐 워커를 관리한다.

- 패키지: `laravel/horizon`
- 프로바이더 등록: `bootstrap/providers.php`
- 설정 파일: `config/horizon.php`

## 3. 큐 레인 정책

현재 레인:

1. `critical`: 사용자 영향도가 큰 고우선 작업
2. `mail`: 메일 발송
3. `sms`: 문자 발송
4. `chat`: 채팅 비동기 처리
5. `push`: 푸시 발송
6. `default`: 일반 비동기 작업
7. `maintenance`: 정리/백필/유지보수 작업

레인별 대기 임계값(`waits`)을 분리해 정체를 빠르게 감지한다.

## 4. Horizon Supervisor 매핑

`config/horizon.php` 기준:

1. `supervisor-critical` -> `critical`
2. `supervisor-mail` -> `mail`
3. `supervisor-sms` -> `sms`
4. `supervisor-chat` -> `chat`
5. `supervisor-push` -> `push`
6. `supervisor-default` -> `default`
7. `supervisor-maintenance` -> `maintenance`

환경별(`production`, `local`)로 `maxProcesses`가 분리돼 있다.

## 5. 테이블 역할 (Queue 관점)

1. `jobs`
- `database` 큐 드라이버에서 대기열 저장에 사용
- 현재 표준 운영은 Redis이므로 주 사용 대상은 아님

2. `job_batches`
- `Bus::batch()` 메타데이터 저장용
- 큐 백엔드가 Redis여도 DB 테이블 사용

3. `failed_jobs`
- 실패한 큐 작업 저장
- 재시도/원인분석/감사 용도

## 6. 현재 실제 라우팅

1. 공지 푸시 발송
- 클래스: `app/Domains/Notice/Jobs/SendNoticePushJob.php`
- 라우팅: `connection=redis`, `queue=push`

## 7. Job 작성 규칙

1. `connection`, `queue`, `tries`, `timeout` 명시
2. 멱등성 보장(중복 실행 안전)
3. 레인 목적 혼합 금지
4. Job 내부에서 외부 API 호출 시 재시도/백오프 정책 명시

## 8. 운영 명령어

```bash
php artisan horizon:status
php artisan horizon:terminate
php artisan queue:failed
php artisan queue:retry all
php artisan queue:prune-failed --hours=168
php artisan queue:prune-batches --hours=72 --unfinished=72 --cancelled=168
```

## 9. 배포 체크리스트

1. 코드 배포
2. `php artisan migrate --force`
3. `php artisan config:cache` (운영 정책에 따라)
4. `php artisan horizon:terminate`로 워커 재기동
5. `php artisan horizon:status` 확인

## 10. 장애 대응

### 10.1 큐 적체

1. Horizon 대시보드에서 레인별 적체 확인
2. 특정 레인만 적체 시 해당 supervisor `maxProcesses` 상향 검토
3. 느린 Job 샘플링 후 로직/외부 API 병목 확인

### 10.2 실패 Job 급증

1. `failed_jobs` 원인 분석
2. 일시 장애면 재시도, 영구 오류면 코드 수정 후 재처리
3. 실패 누적 과도 시 `queue:prune-failed` 정책 점검

## 11. 운영 원칙

1. Horizon 운영 환경에서는 `queue:listen` 상시 실행을 지양한다.
2. 큐 소비자는 Horizon 단일 체계로 운영한다.
3. 레인 추가/수정 시 `config/horizon.php`와 문서를 함께 갱신한다.
