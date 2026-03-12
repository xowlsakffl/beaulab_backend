# Scheduler 운영 가이드

이 문서는 Beaulab 프로젝트의 스케줄 실행 구조(`crontab`, Laravel Scheduler, Spatie Schedule Monitor)를 정리한다.  
큐/호라이즌 상세는 `queue.md`에서 관리한다.

## 1. 목적

1. 시간 기반 작업을 일관된 방식으로 운영한다.
2. 스케줄 누락/실패를 조기에 감지한다.
3. 배포 후 스케줄 동기화 누락을 방지한다.

## 2. 실행 구조

1. OS `crontab`이 매분 `php artisan schedule:run` 실행
2. Laravel Scheduler가 `routes/console.php` 등록 작업 실행
3. 작업 결과를 Spatie Schedule Monitor가 DB에 기록

핵심 구분:

- `crontab`: 스케줄러 시동 트리거
- `Laravel Scheduler`: 실행 대상 정의
- `Spatie Schedule Monitor`: 실행 상태 감시/기록

## 3. 현재 등록 스케줄

`routes/console.php` 기준:

1. `schedule-monitor:sync` 매일 02:50
2. `notice:cleanup-temp-editor-images --hours=24` 매시
3. `horizon:snapshot` 5분마다
4. `queue:prune-batches --hours=72 --unfinished=72 --cancelled=168` 매일 03:10
5. `queue:prune-failed --hours=168` 매일 03:20

## 4. Schedule Monitor 테이블 역할

1. `monitored_scheduled_tasks`
- 모니터링 대상 스케줄 레지스트리
- `schedule-monitor:sync` 실행 시 동기화

2. `monitored_scheduled_task_log_items`
- 실행 이벤트 로그(`started`, `finished`, `failed`, `skipped`)

## 5. 동기화 규칙

스케줄을 추가/수정/삭제한 뒤에는 반드시 아래를 실행한다.

```bash
php artisan schedule-monitor:sync
```

확인:

```bash
php artisan schedule:list
php artisan schedule-monitor:list
```

## 6. 서버 운영 기준

### 6.1 crontab

운영 서버 예시:

```cron
* * * * * cd /root/beaulab && php artisan schedule:run >> /dev/null 2>&1
```

### 6.2 점검 명령

```bash
php artisan schedule:list
php artisan schedule-monitor:sync
php artisan schedule-monitor:list
```

## 7. 배포 체크리스트

1. 코드 배포
2. `php artisan migrate --force`
3. `php artisan schedule-monitor:sync`
4. `php artisan schedule:list`로 등록 상태 확인
5. `php artisan schedule-monitor:list`로 모니터 등록 상태 확인

## 8. 장애 대응

### 8.1 스케줄이 전혀 실행되지 않음

1. crontab 등록 확인
2. `schedule:run` 경로/권한 확인
3. 앱 루트/환경 변수 로딩 여부 확인

### 8.2 특정 작업만 누락

1. `php artisan schedule:list`에서 Next Due 확인
2. `php artisan schedule-monitor:list`에서 실패/지연 확인
3. 해당 명령 단독 실행 후 예외 확인

## 9. 운영 원칙

1. 시간 기반 트리거는 Scheduler를 사용한다.
2. API 요청 기반 즉시 실행은 Scheduler를 거치지 않고 직접 Queue를 발행할 수 있다.
3. 스케줄 변경 시 코드와 문서를 동시에 갱신한다.
