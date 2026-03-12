# Logging 전략 (Audit Log / App Log 분리)

이 문서는 Beaulab 프로젝트의 로그 전략을 정리한다.  
핵심은 **감사로그(Audit Log)** 와 **운영로그(App Log)** 를 분리하고,  
감사 대상 데이터의 변경 이력을 신뢰 가능하게 남기는 것이다.

---

## 1. 목적

- 운영 이슈 추적(예외/장애 분석)과 변경 이력 추적(감사)을 분리
- CUD 변경의 주체/대상/차이를 추적 가능하게 유지
- 권한(Role/Permission) 변경 이력도 도메인 변경과 동일하게 기록

---

## 2. 로그 종류와 저장소

## 2.1 감사로그 (Audit Log)

- 저장소: DB `activity_log` 테이블
- 로거: `spatie/laravel-activitylog`
- 용도: 데이터 변경 이력(누가/무엇을/어떻게)
- 로그명: `audit`

현재 구현 기준:
- 감사 대상 모델은 `HasAuditLogs` trait 사용
- trait에서 아래 옵션을 고정한다.
  - `useLogName('audit')`
  - `logFillable()`
  - `logOnlyDirty()`
  - `dontSubmitEmptyLogs()`

## 2.2 운영로그 (App Log)

- 저장소: `storage/logs/laravel.log` (기본 channel)
- 로거: Laravel Logging (Monolog)
- 용도:
  - 예외/경고/디버그 로그
  - traceId 기반 요청 추적
  - 인프라 및 런타임 운영 이벤트

## 2.3 비동기 운영로그 (Queue / Scheduler)

- Queue 실패/재시도 상태
  - 저장소: `failed_jobs` 테이블 + Horizon 대시보드
- Scheduler 실행 상태
  - 저장소: `monitored_scheduled_tasks`, `monitored_scheduled_task_log_items`
- 용도:
  - 배치/정리 작업 누락 감지
  - 특정 레인(`mail`, `push` 등) 적체/실패 추적
  - 스케줄 실행 실패 원인 분석

---

## 3. 감사 대상 모델 변경 정책 (중요)

감사 대상 모델에서는 **벌크 update/delete를 금지**한다.

- 금지 예시
  - `Model::query()->update([...])`
  - `Model::where(...)->delete()`
- 허용 예시
  - 개별 모델을 조회 후 `save()`
  - 개별 모델 인스턴스 `delete()`

이유:
- 감사로그는 Eloquent 모델 이벤트 기반으로 남는다.
- 벌크 쿼리는 모델 이벤트를 우회할 수 있어 이력 누락 위험이 있다.

---

## 4. 기본 기록 범위

현 단계 운영 원칙:

1. **웬만한 모든 도메인 CUD(Create/Update/Delete) 기록**  
2. **모든 도메인 권한 변경사항 기록**
   - Role 부여/회수
   - Permission 부여/회수
   - `syncRoles`, `syncPermissions` 등 동기화 작업

권한 변경 로직은 되도록 Action 레이어로 모아 기록 포인트를 단일화한다.

---

## 5. 구현 체크리스트

- [ ] 감사 대상 모델이 `HasAuditLogs`를 사용하고 있는가?
- [ ] 해당 모델 변경에 bulk update/delete가 없는가?
- [ ] CUD 시 변경 주체(causer)와 대상(subject) 식별이 가능한가?
- [ ] 권한 변경 API/Action에서 감사로그를 남기고 있는가?
- [ ] 운영로그에 traceId/path/method가 포함되는가?

---

## 6. 운영 시 주의사항

- 감사로그는 보관 기간/정리 정책을 함께 운영한다.
- 운영로그에는 민감정보(토큰, 주민번호, 전체 SQL 바인딩 등)를 남기지 않는다.
- 장애 분석은 App Log 중심으로, 변경 추적/감사는 Audit Log 중심으로 수행한다.
- 비동기 장애 분석 시 `laravel.log`만 보지 말고 `failed_jobs`, Horizon, schedule-monitor 로그를 함께 확인한다.

---

작성 기준: 2026-03-12
