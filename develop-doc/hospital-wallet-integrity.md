# 병의원 충전금 원장 안전 규칙

## 충전·소진 기록 진입점

유상 충전과 상품 결제 소진은 지갑 테이블을 직접 갱신하지 않는다.

- 유상 충전 완료: `HospitalWalletChargeCompleteAction`
- 충전금 소진 완료: `HospitalWalletUsageCompleteAction`
- 충전은 `HospitalWalletChargeData`, 소진은 `HospitalWalletUsageData`로 입력 계약을 고정한다.
- 두 Action 모두 지갑 행을 `lockForUpdate()`한 뒤 Operation, Transaction, Entry, 지갑 잔액을 하나의 DB transaction에서 처리한다.
- 소진은 유상·무상 포인트의 차감 순서를 Action이 임의로 결정하지 않는다. 호출 도메인이 `paidPoints`, `servicePoints`를 명시한다.
- 외부 결제와 상품 구매 호출부는 재시도에도 바뀌지 않는 업무 키를 `idempotencyKey`로 전달한다.

## 원장 무결성

- `reserved_paid_balance <= paid_balance`
- Operation, Transaction, Entry의 금액은 0보다 커야 한다.
- Operation의 유형과 상태는 모델에 정의된 값만 저장할 수 있다.
- Payment와 Refund의 최종 금액은 공급가액과 부가세의 합이어야 한다.
- Transaction과 Entry는 Eloquent 모델에서 수정·삭제를 거부한다. Query Builder나 직접 SQL로 원장을 변경하지 않으며, 정정은 반대 방향의 별도 Operation과 Transaction으로 남긴다.
- `php artisan hospital-wallet:audit-integrity`는 예약금, 처리 상태, 원장 연결, Entry 합계, 잔액 스냅샷을 읽기 전용으로 검사한다.
- 스케줄러는 매일 03:40에 무결성 감사를 실행하고 위반이 있으면 critical 로그를 남긴다.

## 환불 보안과 재시도

- 환불 서류 조회·다운로드는 `beaulab.hospital_wallet.refund_document_show` 또는 환불 처리 권한이 있어야 한다.
- 내역 조회 권한만으로 사업자등록증과 통장 사본을 열 수 없다.
- 환불 금액 계산은 `HospitalWalletRefundAmount`를 단일 기준으로 사용한다.
- 프론트는 실패 여부가 불명확한 요청의 멱등 키를 `sessionStorage`에 유지하고 서버 성공 응답을 확인한 뒤에만 폐기한다.

## 삭제 병의원

- 삭제된 병의원의 기존 지갑과 원장은 정산·감사를 위해 목록과 내역에 유지한다.
- 관리자 목록에는 삭제 상태를 명시하고 신규 지급·회수·환불 선택은 막는다.
- 서버도 신규 잔액 변경 시 활성 병의원 관계를 확인한다.
