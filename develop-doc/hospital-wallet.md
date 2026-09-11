# 충전금

충전금 업무 상태와 실제 잔액 원장을 분리합니다. 화면 단순화나 코드 공통화를 위해 이 경계를 없애지 않습니다.

## 구조와 잔액

```text
HospitalWalletOperation: 신청·대기·완료·반려 등 업무 1건
  → 실제 잔액 변경 완료 시 HospitalWalletTransaction 1건
    → HospitalWalletTransactionEntry: 유상/서비스 증감
```

[모델](../app/Domains/HospitalWallet/Models)과 [마이그레이션](../database/migrations)이 제약의 기준입니다.

| 데이터      | 책임                                                     |
| ----------- | -------------------------------------------------------- |
| Wallet      | 현재 유상·예약·서비스 잔액 스냅샷                        |
| Operation   | 유형, 업무 상태, 금액, 요청/처리자, 대상 스냅샷, 멱등 키 |
| Transaction | 완료된 Operation과 1:1인 불변 원장, 변경 전후 잔액       |
| Entry       | PAID/SERVICE별 CREDIT/DEBIT 증감                         |
| Payment     | 결제 수단·외부 거래 ID·공급가액·VAT·총 결제금액          |
| Refund      | 환불 금액·암호화 계좌·반려 사유·첨부서류                 |

잔액의 의미:

```text
보유 유상 포인트 = paid_balance
예약 유상 포인트 = reserved_paid_balance
사용 가능한 유상 포인트 = paid_balance - reserved_paid_balance
사용 가능한 전체 포인트 = paid_balance - reserved_paid_balance + service_balance
```

목록의 충전/전체 잔여 포인트와 문자 변수는 사용 가능한 잔액입니다.
예약된 환불 포인트를 다시 사용하거나 새 환불에 중복 예약하지 않습니다.
Operation.amount는 포인트, Payment/Refund의 금액 필드는 원화입니다.

Operation의 대상 표시명은 당시 reference_label 스냅샷을 보존합니다.
병원 삭제 후에도 기존 지갑·원장은 정산과 감사를 위해 유지하되, 삭제 병원에 신규 지급·회수·환불 등 잔액 변경은 허용하지 않습니다.

## 변경 진입점과 불변 조건

[Actions](../app/Domains/HospitalWallet/Actions), [Data](../app/Domains/HospitalWallet/Data), [Support](../app/Domains/HospitalWallet/Support)를 먼저 확인합니다.

- 충전 완료는 `HospitalWalletChargeCompleteAction` + `HospitalWalletChargeData`.
- 소진 완료는 `HospitalWalletUsageCompleteAction` + `HospitalWalletUsageData`.
- 소진 호출 도메인이 paidPoints/servicePoints를 명시합니다. 공통 Action이 임의로 차감 순서를 정하지 않습니다.
- 지갑 잠금, Operation, Transaction/Entry, 잔액 갱신은 같은 DB 트랜잭션에서 처리합니다.
- 잔액은 음수가 될 수 없고 `reserved_paid_balance <= paid_balance`여야 합니다.
- Operation/Transaction/Entry의 처리 금액은 양수, 결제·환불 총액은 공급가액+VAT여야 합니다.
- 완료된 Transaction과 Entry는 수정·삭제하지 않습니다. Eloquent 보호를 Query Builder·직접 SQL로 우회하지 않습니다.
- 정정은 별도 업무와 반대 방향 원장으로 기록합니다. 과거 원장이나 잔액 숫자를 수동으로 맞추지 않습니다.

외부 결제·상품 구매 호출부는 재시도에도 유지되는 업무 키를 idempotencyKey로 전달해야 합니다.
동일 키와 동일 payload는 기존 결과를 반환하고, 동일 키에 다른 내용은 거부합니다.
네트워크 실패 때마다 키를 새로 만들면 중복 금액 변경을 막을 수 없습니다. 프론트도 성공 확인 전까지 키를 유지합니다.

Payment에 외부 거래 필드가 있다는 것만으로 실제 PG 연동과 정산 검증까지 끝났다고 판단하지 않습니다.

## 서비스 지급·회수

- 대상 지갑을 병원 ID 순서로 잠급니다. 다른 일괄 경로에서도 잠금 순서를 맞춥니다.
- 요청 idempotency_key를 batch_uuid로 사용하고 병원별 Operation 키를 파생합니다.
- 재시도는 병원 목록·유형·금액·사유까지 일치해야 합니다.
- 회수는 모든 병원의 서비스 잔액을 검증한 후 실행합니다. 한 곳이라도 부족하면 전체를 롤백합니다.
- 프론트 경고 문구와 별개로 서버에서 실제 잠긴 잔액을 검사합니다.

## 환불

| 권한                                           | 역할                    |
| ---------------------------------------------- | ----------------------- |
| `beaulab.hospital_wallet.refund_request`       | 환불 신청               |
| `beaulab.hospital_wallet.refund_process`       | 완료·반려 처리          |
| `beaulab.hospital_wallet.refund_document_show` | 환불 서류 조회·다운로드 |

일반 내역 조회 권한만으로 민감 서류를 열 수 없습니다. 처리 권한자의 서류 접근은 Policy를 따릅니다.
최고관리자는 신청 API에서 즉시 완료 처리합니다. 일반 신청과 최종 처리 권한을 합치지 않습니다.

| 동작                 | Operation | 잔액·원장                                     |
| -------------------- | --------- | --------------------------------------------- |
| 운영팀 신청          | PENDING   | reserved_paid_balance 증가, 원장 없음         |
| 재무팀 완료          | COMPLETED | paid/reserved 함께 차감, PAID/DEBIT 원장 생성 |
| 재무팀 반려          | REJECTED  | 예약 해제, 원장 없음                          |
| 최고관리자 즉시 처리 | COMPLETED | 유상 잔액 차감과 원장을 함께 생성             |

환불은 사용 가능한 유상 포인트만 대상으로 하며 서비스 포인트는 환불하지 않습니다.
신청·처리 시 지갑과 Operation을 잠그고 현재 상태와 멱등 키를 검증합니다. 두 번 승인하거나 반려 후 승인하는 요청을 UI만으로 막지 않습니다.

계산의 서버 기준은 `HospitalWalletRefundAmount`입니다.

```text
공급가액 = 환불 포인트
VAT = 공급가액의 10%를 반올림
환불 금액 = 공급가액 + VAT
```

양방향 입력 UI가 계산한 값을 신뢰해서 그대로 저장하지 않습니다. 서버의 금액 계약과 일치하는지 검증합니다.
환불 계좌번호는 encrypted cast로 저장하고 일반 목록에는 반환하지 않습니다.
사업자등록증·통장사본은 선택 첨부이며 각각 별도 비공개 Media collection을 사용합니다.
서류 수정은 조회 권한에 더해 신청 또는 처리 권한이 필요하며 PENDING에서만 가능합니다. 완료·반려 후에는 교체/삭제하지 못합니다.

## 목록과 현황의 의미

실제 경로·검색 필드는 [Staff 라우트](../app/Modules/Staff/routes/api_staff.php)와 [Queries](../app/Domains/HospitalWallet/Queries)를 확인합니다.

- 내역 type_group은 CHARGE/USAGE/REFUND/SERVICE/ALL이며 기본은 CHARGE입니다.
- ALL에서 검색어와 hospital_id가 모두 없으면 대량 USAGE를 제외합니다. 누락 버그로 보고 무조건 포함시키지 않습니다.
- 통합 검색은 병원명·담당 관리자이며 작업 ID는 검색 대상이 아닙니다.
- 소진·서비스 적립/회수도 내부 상태는 COMPLETED지만, 화면 상태는 충전·환불에만 보여주고 나머지는 `-`입니다.

현황 집계:

| 지표                | 기준                                                       |
| ------------------- | ---------------------------------------------------------- |
| 연간 입금·사용·환불 | 조회 연도, COMPLETED 업무의 Transaction 생성 시각          |
| 월별 추이           | 현재 월 포함 최근 6개월, 서비스 적립/회수·충전·소진·환불   |
| 총 잔여 포인트      | 전체 지갑의 가용 유상+서비스, 환불 예약분 제외             |
| 소진 TOP5           | 기간 내 USAGE+COMPLETED+DEBIT Entry 합계, 전체/유상/서비스 |
| 부위별 비율         | 삭제되지 않은 상담신청과 현재 이벤트의 대표 카테고리 연결  |

모든 현황 수치 단위는 포인트(P)이며 VAT 포함 현금 매출과 같지 않습니다.
상위 병원은 소진액 내림차순, 동률이면 병원명 가나다순입니다.
부위별 비율은 SURGERY/TREATMENT의 최상위 카테고리로 합산합니다. 이벤트 분류 변경은 과거 신청의 차트에도 영향을 줍니다.
신청 당시 분류 고정이 필요해지면 신청 데이터에 별도 스냅샷을 설계해야 하며 현재 기능이 이를 보장한다고 설명하지 않습니다.

## 잔액 안내 문자

수신처는 `hospitals.ad_reception_phone_1`의 국내 휴대전화 형식 연락처입니다.
미신청 병원에는 아직 없을 수 있으므로 제외 건수를 구분합니다.
계정 인증 연락처는 이메일이며 대표자 문자 옵션은 사용하지 않습니다. 호환 필드 send_to_representative는 false만 허용합니다.
발송 대기 등록과 실제 전송 성공은 다릅니다. [SMS 처리](messaging.md)를 확인하세요.

## 무결성 확인

```sh
php artisan hospital-wallet:audit-integrity
```

업무 데이터를 변경하지 않고 예약액, 처리 상태, 원장 연결, Entry 합계와 잔액 스냅샷을 검사합니다.
위반 시 실패 코드와 critical 로그를 남기며 매일 03:40 스케줄로도 실행합니다.

신규 금액 처리 경로는 정상 처리 외에 동시 요청·동일 키 재시도·다른 payload·부족 잔액·중간 실패 롤백을 확인합니다.
운영 계좌 송금이나 실제 잔액 변경을 점검용으로 실행하지 않습니다.
인덱스는 병원별 최신순, 유형/상태별 목록, batch_uuid와 멱등 키 조회에 맞추고 실제 실행 계획을 확인합니다.
