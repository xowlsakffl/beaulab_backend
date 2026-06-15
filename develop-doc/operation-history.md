# 운영 히스토리 설계

작성 기준: 2026-06-15

이 문서는 Staff 운영 화면에서 표시하는 처리 이력 구조를 정리한다.  
운영 히스토리는 `activity_log` 감사 로그와 목적이 다르다. `activity_log`는 모델 변경 감사 추적용이고, `operation_histories`는 관리자 화면에 노출되는 업무 처리 이력이다.

## 1) 핵심 목적

- 관리자 화면에서 노출/미노출, 신고 처리, 영수증 인증, 이벤트 검수, 병원 상태 변경 같은 운영 행위를 사람이 읽을 수 있게 표시한다.
- 단일 필드 변경과 여러 필드 동시 변경을 같은 구조로 기록한다.
- 변경 전/후 원본 값과 화면 표시값을 함께 저장해, 나중에 상태 라벨이나 포맷 규칙이 바뀌어도 당시 이력을 안정적으로 보여준다.

## 2) 테이블 구조

| 테이블 | 역할 |
|---|---|
| `operation_histories` | 이력 부모 행. 대상, 수행자, 액션, 사유, 메타데이터를 저장 |
| `operation_history_changes` | 이력 상세 변경 행. 변경 필드별 전/후 값을 저장 |

`operation_histories` 주요 컬럼:

| 컬럼 | 의미 |
|---|---|
| `target_type`, `target_id` | 이력이 붙는 대상 모델 |
| `actor_type`, `actor_id`, `actor_kind` | 처리한 관리자/사용자/시스템 |
| `action` | 수행 액션 코드 |
| `batch_uuid` | 일괄 처리 묶음 UUID |
| `reason` | 처리 사유 |
| `metadata` | 추가 메타데이터 |

`operation_history_changes` 주요 컬럼:

| 컬럼 | 의미 |
|---|---|
| `operation_history_id` | 부모 이력 ID |
| `field_key` | 변경 필드 키 |
| `field_label` | 화면 표시 필드명 |
| `before_value`, `after_value` | 변경 전/후 원본 값 |
| `before_display`, `after_display` | 변경 전/후 표시값 |
| `sort_order` | 표시 순서 |

## 3) 모델 구조

- `OperationHistory`
  - `changes()` hasMany relation을 가진다.
  - 기본 조회 시 `changes`를 함께 로드한다.
  - 구버전 응답 호환을 위해 `field`, `before_value`, `after_value` accessor는 첫 번째 change를 반환한다.
- `OperationHistoryChange`
  - `operation_history_changes` 테이블 모델이다.
  - `before_value`, `after_value`는 JSON 캐스팅한다.

## 4) 기록 방식

운영 이력 생성은 `OperationHistoryCreateAction`만 사용한다.

```php
$this->historyCreateAction->execute(
    target: $target,
    action: OperationHistory::ACTION_UPDATED,
    actor: $staff,
    reason: $reason,
    changes: OperationHistoryChangeSetBuilder::single(
        key: 'status',
        label: '노출상태',
        before: $before,
        after: $after,
        beforeDisplay: '노출',
        afterDisplay: '미노출',
    ),
);
```

단건 변경은 `OperationHistoryChangeSetBuilder::single()`을 사용한다.  
여러 필드 변경은 `OperationHistoryChangeSetBuilder::make()->compare(...)->compare(...)->toArray()`로 만든다.  
수정 전/후 스냅샷을 이미 가지고 있는 경우에는 `OperationHistoryChangeSetBuilder::fromSnapshots($before, $after)`를 사용한다.

도메인 Action에서는 직접 `operation_history_changes`를 만들지 않는다. 변경 payload 조립은 공통 builder를 사용하고, 저장은 `OperationHistoryCreateAction`에 맡긴다.

## 5) 다중 변경 처리

이벤트 수정처럼 여러 필드가 한 번에 바뀌는 경우에도 부모 이력은 1건만 만든다.

예시:

```text
operation_histories
- action: UPDATED
- reason: null

operation_history_changes
- 이벤트명: 변경 전/후
- 이벤트 설명: 변경 전/후
- 이벤트 기간: 변경 전/후
- 이벤트 가격: 변경 전/후
```

프론트는 부모 이력 1건 아래의 `changes` 배열을 순서대로 표시한다.

## 6) 현재 적용 도메인

현재 `OperationHistoryCreateAction`을 쓰는 도메인은 모두 `changes` 구조를 사용한다.

- `Talk`
- `TalkComment`
- `HospitalReview`
- `HospitalReviewComment`
- `HospitalEvaluation`
- `Hospital`
- `HospitalDoctor`
- `HospitalEvent`
- `HospitalVideo`
- `Notice`
- `Faq`
- `Category`
- `Hashtag`
- `AccountUser`
- `Beauty`
- `BeautyExpert`
- `ContentReport`
- `ContentReportState`

## 7) API 응답 기준

`OperationHistoryDto`는 다음 구조를 내려준다.

```json
{
  "id": 1,
  "target": {
    "type": "App\\Domains\\Talk\\Models\\Talk",
    "id": 10
  },
  "actor": {
    "kind": "STAFF",
    "label": "관리자"
  },
  "action": "UPDATED",
  "reason": "관리자 미노출",
  "field": "status",
  "before_value": "ACTIVE",
  "after_value": "INACTIVE",
  "changes": [
    {
      "field_key": "status",
      "field_label": "노출상태",
      "before_value": "ACTIVE",
      "after_value": "INACTIVE",
      "before_display": "노출",
      "after_display": "미노출"
    }
  ]
}
```

`field`, `before_value`, `after_value`는 기존 프론트 호환용이다. 신규 화면은 `changes` 배열을 기준으로 렌더링한다.

## 8) 구현 규칙

- 새 운영 이력은 반드시 `changes`를 사용한다.
- `operation_histories`에 변경 필드 컬럼을 다시 추가하지 않는다.
- 화면 표시 문구가 필요한 값은 `before_display`, `after_display`를 함께 저장한다.
- 단순 상태 변경도 `changes` 배열 1건으로 저장한다.
- 생성 이력은 `CREATED`, 수정 이력은 `UPDATED`, 상태 전용 처리 이력은 `STATUS_UPDATED`를 사용한다.
- 생성 이력은 최초 입력값 전체를 `changes`에 남기지 않는다. 부모 이력 1건만 남기고 `reason`은 null로 둬 화면에서 `-`로 표시한다.
- 한 번의 저장/수정 요청에서 여러 필드가 바뀌면 부모 이력 1건에 change 여러 건을 붙인다.
- 스태프 관리 화면의 일반 수정 기능은 각 도메인별 `*UpdateHistoryRecordAction`에서 수정 전/후 스냅샷을 잡아 기록한다.
- 목록 필터나 summary에서 특정 변경 필드를 봐야 하면 `operation_history_changes.field_key` 기준으로 조회한다.
- 기존 코드 호환이 필요한 DTO 외에는 `history->field`, `history->after_value` accessor에 의존하지 않는다.
