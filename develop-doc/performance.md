# 성능 / 인덱스 / 쿼리 규칙

작성 기준: 2026-06-29

이 문서는 백엔드 목록/summary/selector API의 성능 기준과 인덱스 추가 원칙을 정리한다.

## 1) 기본 원칙

- 성능 개선은 추측이 아니라 측정값을 기준으로 판단한다.
- 목록 API는 `ListForStaffQuery`, summary API는 `SummaryForStaffQuery`, selector/option API는 별도 Query에 둔다.
- Controller와 Action에 DB 조건을 흩뿌리지 않는다.
- summary, list, option이 서로 의존하지 않으면 프론트에서 병렬 호출할 수 있도록 API를 독립적으로 유지한다.
- 목록 정렬은 같은 값이 여러 행에 있을 수 있으므로 `id` tie-breaker를 둔다.
- `LIKE "%keyword%"` 검색은 일반 BTREE 인덱스 효과가 제한적이다. 데이터가 커지면 fulltext 또는 별도 검색 전략을 검토한다.

## 2) 인덱스 원칙

- 현재 프로젝트는 스키마를 fresh 기준으로 관리하므로, 새 인덱스는 별도 add migration보다 기존 create migration에 반영하는 것을 기본으로 한다.
- 운영 DB가 있는 상태에서 배포할 경우에는 별도 migration이 필요하다. 이때는 배포 절차 문서에서 별도로 결정한다.
- 인덱스는 실제 where/order/join/group by 패턴이 있는 컬럼에만 둔다.
- 복합 인덱스가 단일 인덱스의 left-prefix를 이미 만족하면 중복 단일 인덱스를 만들지 않는다.
- unique 인덱스와 동일한 일반 인덱스를 중복 생성하지 않는다.
- polymorphic relation은 `model_type, model_id, collection` 또는 `target_type, target_id` 조회 패턴에 맞춰 복합 인덱스를 둔다.

## 3) Query 작성 규칙

- Staff 목록은 기본적으로 `paginate()`를 사용하고 `PaginatedResponse`로 응답한다.
- 목록 DTO에서 필요한 relation은 Query의 `with()` / `withCount()` / `withSum()`에서 명시한다.
- N+1을 숨기기 위해 DTO에서 relation을 새로 조회하지 않는다.
- summary는 가능한 한 여러 count를 `selectRaw SUM(CASE WHEN ...)` 또는 `groupBy()->pluck()`로 묶는다.
- 상태 라벨은 프론트에서 임의로 만들지 않고 모델의 `statusLabel()` / `allowStatusLabel()`을 기준으로 DTO에서 내려준다.
- 도메인 모델에 이미 relation/scope가 있으면 Action에서 raw `loadCount()`를 반복하지 말고 모델 메서드 또는 Query로 모은다.

## 4) 현재 정리된 영역

- 병원 목록/summary: dormant count와 검수/정지/탈퇴 summary를 Query로 분리하고, 신규 이벤트 DB 카운트는 모델 relation 기준으로 처리한다.
- 이벤트 목록: 상담신청 수, 확정 상담신청 수, 사용 포인트 합계를 목록 Query에서 eager aggregate로 처리한다.
- 이벤트 DB/리얼모델 DB: 회원/병원/이벤트 relation을 목록 Query에서 eager load한다.
- 회원 목록/summary: 접속자/가입경로/상태 summary를 Query로 분리하고, 목록 정렬에 id tie-breaker를 둔다.
- 신고게시물 summary: `ContentReportSummaryCache`로 5분 캐싱하고 상태 변경/신고 생성 시 무효화한다.
- selector/option API: 병원 옵션, 의료진 옵션, 카테고리 selector는 프론트 request cache와 함께 중복 호출을 줄인다.
- placeholder route/menu는 권한 매핑이 없는 직접 접근을 fail-closed로 처리한다.

## 5) 최근 측정 기준

fresh seed 기준 Query 레이어 측정값이다. 데이터가 작으므로 실서비스 대용량 성능을 보장하지 않는다.

| 페이지/호출 묶음 | wall median | wall avg | DB avg | query count |
|---|---:|---:|---:|---:|
| 병의원 목록 core | 4.26ms | 4.41ms | 2.08ms | 7 |
| 이벤트 목록 core | 11.66ms | 12.32ms | 5.66ms | 18 |
| 회원 목록 core | 1.82ms | 1.97ms | 0.96ms | 5 |
| 의료진 목록 core | 5.38ms | 6.10ms | 2.72ms | 8 |
| 입점신청 목록 core | 1.04ms | 1.12ms | 0.55ms | 3 |
| 이벤트 DB 목록 | 2.91ms | 3.07ms | 1.26ms | 6 |
| 리얼모델 DB 목록 | 2.69ms | 2.95ms | 1.32ms | 5 |

주의:
- 이 값은 DB/Query 계층 측정이다.
- 브라우저 체감 시간이 느리면 프론트 dev server, HTTP 왕복, 인증 복구, React 렌더링, 이미지 처리, 중복 fetch를 분리해서 본다.
- 예전 측정값과 비교하려면 같은 seed, 같은 schema, 같은 code revision이 필요하다.

## 6) 배포 전 성능 체크

- `php artisan migrate:status`로 migration 상태 확인
- 주요 목록 Query 레이어 반복 측정
- 프론트 Network에서 summary/list/options가 불필요하게 직렬 호출되는지 확인
- 이미지 preload가 table 렌더를 막지 않는지 확인
- production seed 또는 복제 데이터로 병원/이벤트/회원/DB 목록을 다시 측정
