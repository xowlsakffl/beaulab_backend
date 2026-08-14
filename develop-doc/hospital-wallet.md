# 병의원 충전금 설계

작성 기준: 2026-08-12

## 1. 핵심 원칙

충전금 업무 상태와 실제 잔액 원장을 분리한다.

```text
HospitalWalletOperation(신청·대기·완료·취소)
  -> 완료된 경우에만 HospitalWalletTransaction(불변 원장) 1건
    -> HospitalWalletTransactionEntry(유상/서비스 증감 상세)
```

- `Operation`은 입금대기, 취소처럼 잔액이 변하지 않은 업무 건도 보존한다.
- `Transaction`은 실제 잔액 변경이 완료된 경우에만 생성한다.
- 완료된 Operation과 Transaction은 1:1이다.
- Transaction과 Entry는 생성 후 수정·삭제하지 않는다. 정정은 별도 업무 건과 반대 방향 원장으로 처리한다.
- 지갑 잠금, Operation 생성, Transaction/Entry 생성, 잔액 반영은 같은 DB transaction에서 처리한다.

## 2. 테이블 책임

### `hospital_wallets`

병의원별 현재 잔액 스냅샷이다.

- `paid_balance`: 병의원이 보유한 전체 유상 포인트
- `reserved_paid_balance`: 환불 처리 등으로 사용이 예약된 유상 포인트
- `service_balance`: 무상 서비스 포인트
- 사용 가능한 유상 포인트: `paid_balance - reserved_paid_balance`
- 사용 가능한 전체 포인트: 사용 가능한 유상 포인트 + `service_balance`

목록의 `충전 잔여 포인트`, `전체 잔여 포인트`, 문자 템플릿의 `잔여충전금`은 사용 가능한 잔액을 사용한다.

충전금 안내 수신 연락처 정책은 다음과 같다.

- 담당자 연락처는 `hospitals.ad_reception_phone_1`을 사용하며 필수값이다.
- 담당자 연락처는 문자 발송이 가능한 국내 휴대전화 번호 형식이어야 한다.
- 대표자 연락처는 `account_hospitals.phone`을 사용하며 `phone_verified_at`이 기록된 인증 번호만 사용한다.

### `hospital_wallet_operations`

충전, 소진, 환불, 서비스 지급/회수의 업무 단위다.

- 업무 유형과 처리 상태
- 요청자/처리자 polymorphic 정보
- 결제 대상 polymorphic 정보와 표시명 스냅샷
- 처리 사유, 처리 시각, 일괄 처리 UUID
- 요청별 고유 `idempotency_key`

`reference_label`은 연결 대상이 삭제되거나 이름이 바뀌어도 당시 결제 대상을 목록에 표시하기 위한 스냅샷이다.

### `hospital_wallet_transactions`

실제 잔액 변경 원장이다.

- Operation 1건과 1:1
- 변경 전/후 유상·예약·서비스 잔액
- 실제 반영 포인트
- 원장 취소 관계

### `hospital_wallet_transaction_entries`

한 원장에서 유상/서비스 잔액이 각각 얼마나 증가·감소했는지 기록한다.

- `balance_type`: `PAID`, `SERVICE`
- `direction`: `CREDIT`, `DEBIT`

### `hospital_wallet_payments`

유상 충전 업무의 결제 스냅샷이다.

- 결제 수단과 외부 거래 ID
- 공급가액, 부가세, 총 결제금액
- 입금자명과 가상계좌 정보
- 입금 완료 시각

포인트는 Operation의 `amount`, 원화 금액은 Payment의 금액 필드가 기준이다.

### `hospital_wallet_refunds`

환불 업무의 금액·계좌·반려 상세다.

- Operation 1건과 1:1
- `supply_amount`: 환불 포인트와 같은 공급가액
- `vat_amount`: 공급가액의 10%를 반올림한 부가세
- `refund_amount`: 공급가액 + 부가세
- `account_number`: Laravel encrypted cast로 암호화 저장
- 사업자등록증과 통장 사본은 `Media` polymorphic 관계로 각각 별도 collection에 저장

계좌번호는 충전금 내역 목록에 포함하지 않는다. 환불 조회 권한을 통과한 상세 API에서만 복호화해 반환한다.

## 3. 업무 유형과 상태

업무 유형:

| 저장값 | 표시명 |
|---|---|
| `CHARGE` | 입금충전 |
| `USAGE` | 사용 |
| `REFUND` | 환불 |
| `SERVICE_GRANT` | 서비스 적립 |
| `SERVICE_RECLAIM` | 서비스 회수 |
| `REVERSAL` | 거래 취소 |

공통 상태:

| 저장값 | 의미 |
|---|---|
| `PENDING` | 처리 대기 |
| `COMPLETED` | 처리 완료 |
| `CANCELED` | 요청 취소 |
| `REJECTED` | 처리 반려 |
| `FAILED` | 처리 실패 |

화면 문구는 업무 유형에 따라 모델의 `statusLabel()`이 결정한다. 충전은 `입금대기/완료/취소`, 환불은 `환불신청/환불완료/환불반려`를 사용한다.

## 4. Staff API

- `GET /api/v1/staff/hospital-wallets/dashboard`: 연간 요약, 현재 잔액, 월별 추이, 이벤트 DB 카테고리 비율
- `GET /api/v1/staff/hospital-wallets/dashboard/top-hospitals`: 기간·충전금 유형별 소진 상위 병의원
- `GET /api/v1/staff/hospital-wallets`: 병의원별 사용 가능 잔액 목록
- `GET /api/v1/staff/hospital-wallet-operations`: 충전금 내역 목록
- `POST /api/v1/staff/hospital-wallets/service-grants`: 서비스 포인트 일괄 지급
- `POST /api/v1/staff/hospital-wallets/service-reclaims`: 서비스 포인트 일괄 회수
- `GET/POST /api/v1/staff/hospital-wallets/balance-notices`: 충전금 안내 문자 이력/발송
- `POST /api/v1/staff/hospital-wallets/refunds`: 환불 신청 또는 최고관리자 즉시 환불
- `GET /api/v1/staff/hospital-wallet-operations/{operation}/refund`: 환불 상세·첨부서류 조회
- `PATCH /api/v1/staff/hospital-wallet-operations/{operation}/refund`: 환불완료·환불반려 처리

내역 탭의 `type_group`은 `CHARGE`, `USAGE`, `REFUND`, `SERVICE`, `ALL`을 사용한다. 기본값은 `CHARGE`다.

`ALL` 탭은 검색어 또는 `hospital_id`가 없으면 `USAGE`를 제외한다. 전 병의원의 대량 소진 로그가 일반 조회를 압도하는 것을 방지하기 위한 명시적 조회 정책이다.

목록의 통합 검색은 병의원명과 담당 관리자 정보만 조회한다. 작업 ID는 정렬·식별 컬럼으로만 사용하며 통합 검색 대상에 포함하지 않는다.

`ALL` 탭의 상태 컬럼은 업무 상태가 존재하는 충전과 환불에만 표시한다. 즉시 확정되는 소진과 서비스 적립·회수는 내부적으로 `COMPLETED` 상태를 저장하지만 화면에는 `-`로 표시한다.

### 충전금 현황 집계 기준

- 연간 입금·사용·환불과 월별 추이는 `COMPLETED` Operation에 연결된 Transaction 생성 시각을 기준으로 집계한다.
- 화면 단위는 모두 포인트(`P`)다. 부가세 포함 원화 결제액은 현황 포인트와 합산하지 않는다.
- 총 잔여 포인트는 `SUM(paid_balance - reserved_paid_balance + service_balance)`다. 처리 대기 환불로 예약된 유상 포인트는 가용 잔액에서 제외한다.
- 충전금 사용 상위 병의원은 `USAGE + COMPLETED + DEBIT` Entry만 합산하며 `ALL`, `PAID`, `SERVICE`로 구분할 수 있다.
- 이벤트 DB 부위별 비율은 삭제되지 않은 신청을 현재 이벤트의 대표 카테고리와 연결하고, 그 카테고리의 최상위 `SURGERY`/`TREATMENT` 카테고리로 합산한다.
- 현재 이벤트 카테고리를 수정하면 과거 신청의 차트 분류도 바뀐다. 신청 당시 분류를 영구 보존해야 할 경우 `hospital_event_dbs`에 대표 카테고리 스냅샷을 추가해야 한다.

## 5. 서비스 지급/회수

- 지갑은 병의원 ID 순서로 조회하고 `lockForUpdate()`한다.
- 요청의 `idempotency_key`를 `batch_uuid`로 사용하고 병의원별 Operation 키를 파생한다.
- 같은 키의 재시도는 요청 병의원, 유형, 금액, 사유가 모두 같을 때 기존 결과를 반환한다.
- 서비스 회수는 선택된 모든 병의원의 잔액을 먼저 검증한 뒤 일괄 처리한다. 한 곳이라도 부족하면 전체 요청을 롤백한다.

## 6. 환불

권한은 신청과 최종 처리를 분리한다.

- `beaulab.hospital_wallet.refund_request`: 운영팀 환불 신청
- `beaulab.hospital_wallet.refund_process`: 재무팀 환불완료·환불반려 처리
- 최고관리자는 두 권한을 모두 가지며 신청 API 호출 시 `COMPLETED`로 즉시 처리한다.
- 일반 관리자 역할은 신청 권한만 기본 부여한다. 재무 담당자는 계정에 처리 권한을 별도 부여한다.

상태와 잔액 규칙:

1. 운영팀 신청: `PENDING` Operation을 만들고 `reserved_paid_balance`를 증가시킨다. Transaction은 만들지 않는다.
2. 재무팀 완료: `paid_balance`와 `reserved_paid_balance`를 함께 차감하고 PAID/DEBIT Transaction·Entry를 생성한다.
3. 재무팀 반려: `reserved_paid_balance`만 감소시키고 Transaction은 만들지 않는다.
4. 최고관리자 즉시 처리: `COMPLETED` Operation과 PAID/DEBIT 원장을 한 transaction에서 만들고 유상 잔액을 즉시 차감한다.

환불 가능 포인트는 `paid_balance - reserved_paid_balance`다. 서비스 포인트는 환불할 수 없다. 신청·완료·반려는 지갑과 Operation을 `lockForUpdate()`하고 멱등 키를 검증한다.

## 7. 인덱스 기준

- 최신순 목록: `(created_at, id)`
- 병의원별 내역: `(hospital_wallet_id, created_at, id)`
- 탭/상태 목록: `(type, status, created_at, id)`
- 일괄 재시도: `(batch_uuid, id)`와 `idempotency_key unique`
- 요청자/처리자/결제 대상: polymorphic type/id 복합 인덱스

인덱스는 현재 목록 조건과 중복 처리 검증에 필요한 조합만 둔다. 상태 단일 인덱스처럼 선택도가 낮고 실제 쿼리와 맞지 않는 인덱스는 추가하지 않는다.
