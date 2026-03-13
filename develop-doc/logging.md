# 로깅 정리 (App Log / Audit Log / Queue / Scheduler)

작성일: 2026-03-12

## 1) 로그 종류와 저장 위치

### 1-1. 앱 로그 (Laravel Log)
- 저장: `storage/logs/laravel.log`
- 설정: `config/logging.php`
- 기본 채널: `LOG_CHANNEL=stack`, 실제 스택은 `LOG_STACK` 환경변수 사용
- 현재 `.env`: `LOG_STACK=daily` (일 단위 분리 로그)

핵심 파일:
- `config/logging.php`
- `app/Common/Http/Middleware/RequestId.php`
- `bootstrap/app.php`

---

### 1-2. 감사 로그 (Audit Log, DB)
- 저장 테이블: `activity_log`
- 패키지: `spatie/laravel-activitylog`
- 설정: `config/activitylog.php`
- 주요 목적: 모델 생성/수정/삭제 및 권한 변경 이력 추적

핵심 파일:
- `app/Domains/Common/Models/Concerns/HasAuditLogs.php`
- `database/migrations/2026_01_21_052431_create_activity_log_table.php`
- `database/migrations/2026_01_21_052432_add_event_column_to_activity_log_table.php`
- `database/migrations/2026_01_21_052433_add_batch_uuid_column_to_activity_log_table.php`

---

### 1-3. 큐/호라이즌 운영 로그
- 실패 작업 저장: `failed_jobs`
- 배치 상태 저장: `job_batches`
- Horizon 런타임 메타/메트릭: Redis (`config/horizon.php`의 `use`, `prefix`)

핵심 파일:
- `database/migrations/0001_01_01_000002_create_jobs_table.php`
- `config/queue.php`
- `config/horizon.php`
- `routes/console.php` (`horizon:snapshot`, prune 스케줄)

---

### 1-4. 스케줄 모니터 로그 (Spatie Schedule Monitor)
- 작업 마스터: `monitored_scheduled_tasks`
- 실행 로그: `monitored_scheduled_task_log_items`
- 동기화 명령: `schedule-monitor:sync`

핵심 파일:
- `database/migrations/2026_01_21_051900_create_schedule_monitor_tables.php`
- `routes/console.php`

---

### 1-5. 개발/디버그 관측 로그 (Telescope)
- 저장 테이블: `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring`
- 설정: `config/telescope.php`
- 목적: 요청/쿼리/잡/예외 등 디버깅 관측

핵심 파일:
- `database/migrations/2026_01_21_053322_create_telescope_entries_table.php`
- `config/telescope.php`

---

## 2) Trace ID 흐름 (요청 추적)

1. `RequestId` 미들웨어에서 `X-Request-Id` 헤더를 읽거나 UUID 생성
2. request attribute `traceId`에 저장
3. `Log::withContext(['traceId' => ...])`로 모든 로그 컨텍스트 주입
4. 응답 헤더에 `X-Request-Id` 반환
5. 예외 처리 시 `bootstrap/app.php`에서 context(`traceId`, `path`, `method`) 추가
6. API 응답(`ApiResponse`)에도 `traceId` 포함

관련 파일:
- `app/Common/Http/Middleware/RequestId.php`
- `bootstrap/app.php`
- `app/Common/Http/Responses/ApiResponse.php`

---

## 3) 현재 코드에서 실제 기록되는 로그

### 3-1. `Log::info(...)` 앱 로그 지점

인증/계정:
- Staff 로그인/로그아웃/프로필수정/비밀번호변경
- Hospital 로그인/로그아웃/프로필수정/비밀번호변경
- Beauty 로그인/로그아웃/프로필수정/비밀번호변경

관리(Staff):
- 병원/뷰티 생성·수정·삭제·목록 조회
- 일반회원 목록/수정/삭제
- 카테고리 목록/단건/생성/수정/삭제

---

### 3-2. `activity('audit')` 직접 호출 지점
- 파트너 오너 생성 후 role/permission 동기화 이력 기록 2건
  - `HospitalOwnerCreateForStaffAction`
  - `BeautyOwnerCreateForStaffAction`

---

### 3-3. `HasAuditLogs` trait 기반 자동 감사 로그
- 다수 도메인 모델에 `HasAuditLogs` 적용
- 기본 옵션:
  - `useLogName('audit')`
  - `logFillable()`
  - `logOnlyDirty()`
  - `dontSubmitEmptyLogs()`

즉, fillable 변경이 실제로 있을 때만 `activity_log`에 기록됨.

---

## 4) 스케줄로 관리되는 로그/정리 작업

`routes/console.php`
- `schedule-monitor:sync` 매일 02:50
- `notice:cleanup-temp-editor-images --hours=24` 매시간
- `horizon:snapshot` 5분마다
- `queue:prune-batches --hours=72 --unfinished=72 --cancelled=168` 매일 03:10
- `queue:prune-failed --hours=168` 매일 03:20

의미:
- 모니터 대상 스케줄을 DB와 동기화
- 임시 에디터 이미지 정리
- Horizon 메트릭 스냅샷 누적
- 오래된 배치/실패 큐 기록 정리

---

## 5) 운영 조회 명령 예시

앱 로그:
```bash
tail -f storage/logs/laravel.log
```

실패 큐:
```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
```

스케줄 상태:
```bash
php artisan schedule:list
php artisan schedule-monitor:list
```

감사 로그(예시 SQL):
```sql
SELECT id, log_name, event, description, created_at
FROM activity_log
ORDER BY id DESC
LIMIT 50;
```

---

## 6) 운영 주의사항

- 민감정보(비밀번호, 토큰, 주민번호 등)는 로그에 남기지 않는다.
- `activity_log`는 데이터가 계속 증가하므로 주기적 정리 정책이 필요하다.
- 큐/스케줄 장애 분석 시 `laravel.log`만 보지 말고 아래를 함께 본다.
  - `failed_jobs`
  - `job_batches`
  - `monitored_scheduled_task_log_items`
  - Horizon 대시보드
