# 문자 발송 운영 가이드

작성 기준: 2026-08-12

이 문서는 도메인 공통 문자 발송 원장, SMS Provider와 Redis Queue 처리 규칙을 정리한다.

## 1) 코드 위치

- 공통 원장·Provider·Action·Job: `app/Domains/Common/Sms`
- Provider 바인딩: `app/Providers/SmsServiceProvider.php`
- 설정: `config/sms.php`
- 충전금 안내 도메인: `app/Domains/HospitalWallet`
- 병의원 계정 인증 도메인: `app/Domains/AccountHospital`
- Staff API: `app/Modules/Staff/Http/Controllers/HospitalWallet/HospitalWalletForStaffController.php`
- 스키마: `database/migrations/2026_04_10_110500_create_notification_tables.php`

공통 `Sms` 도메인은 `sms_batches`, `sms_deliveries`, Provider 호출, 큐 등록, 재시도와 상태 집계를 소유한다. 충전금·이벤트·입점신청 같은 업무 도메인은 수신자 선정, 문구 치환, `purpose`와 업무별 권한만 관리한다.

`sms_deliveries.reference_type/reference_id`는 문자와 관련된 업무 대상을, `recipient_type/recipient_id`는 실제 수신 계정을 폴리모픽으로 연결한다. 계정이 없는 번호도 발송할 수 있으므로 수신 계정은 nullable이다. 참조 대상이 변경되거나 삭제되어도 감사 이력을 보존할 수 있도록 대상명, 전화번호와 최종 발송 본문은 delivery에 스냅샷으로 저장한다.

병의원 계정 생성 인증번호도 같은 공통 원장과 Redis `sms` 큐를 사용한다. 인증 도메인은 `hospital_account_phone_verifications`에 인증번호 해시와 만료·오입력·일회용 증표만 저장하고, Provider 상태와 발송 본문은 `sms_deliveries`가 소유한다.

## 2) 충전금 안내 수신자

- 담당자: `hospitals.ad_reception_phone_1`
- 대표자: `account_hospitals.phone` 중 `phone_verified_at`이 있는 번호
- 담당자와 대표자 번호가 같으면 한 번만 발송하고 delivery의 `recipient_kinds`에 두 구분을 함께 기록한다.
- 선택한 수신자 번호가 없거나 휴대전화 형식이 아니면 `SKIPPED` delivery로 남긴다.

추가 광고 안내 번호인 `ad_reception_phone_2`, `ad_reception_phone_3`은 현재 충전금 안내 발송 대상이 아니다.

## 3) 문구와 SMS/LMS

충전금 안내 메시지는 자유 문자열 안의 치환 문법을 해석하지 않고 `TEXT`, `VARIABLE` 조각 배열로 받는다. 프론트 편집기에서는 변수 조각을 삭제 가능한 인라인 뱃지로 표시한다.

지원 변수 키:

- `HOSPITAL_NAME`: `hospitals.name`
- `REMAINING_BALANCE`: 유상 잔액과 서비스 잔액의 합계이며 천 단위 쉼표를 포함한다. `P`는 별도 `TEXT` 조각으로 붙인다.

요청 예시:

```json
{
  "message_parts": [
    { "type": "TEXT", "text": "안녕하세요, " },
    { "type": "VARIABLE", "key": "HOSPITAL_NAME" },
    { "type": "TEXT", "text": "의 잔여 충전금은 " },
    { "type": "VARIABLE", "key": "REMAINING_BALANCE" },
    { "type": "TEXT", "text": "P입니다." }
  ]
}
```

백엔드는 허용된 조각 유형과 변수 키만 검증하고, 선택된 병의원의 실제 값을 DB에서 조회해 수신자별 최종 본문을 만든다. 프론트가 병의원명이나 잔액 값을 직접 보내지 않는다.

문자 길이는 ASCII 1바이트, 그 외 문자 2바이트 기준으로 계산한다.

- 90바이트 이하: `SMS`
- 90바이트 초과 2,000바이트 이하: `LMS`
- 2,000바이트 초과: 요청 거부

## 4) 발송 흐름

1. Staff Action에서 `beaulab.hospital_wallet.notice_send` 권한을 확인한다.
2. 지갑 row를 잠그고 발송 시점의 전체 잔액을 문구에 반영한다.
3. 공통 `SmsBatchCreateAction`이 `sms_batches`와 수신자별 `sms_deliveries`를 한 트랜잭션에서 저장한다.
4. 커밋 후 발송 가능한 delivery마다 `SendSmsDeliveryJob`을 Redis `sms` 큐에 등록한다.
5. Job은 `SmsProvider`를 호출하고 수신자별 성공/실패를 기록한다.
6. delivery 변경 후 배치 성공·실패·제외 건수와 최종 상태를 다시 집계한다.

Redis 장애로 최초 dispatch가 실패해 `queued_at`이 없는 delivery는 `sms:dispatch-pending` 명령이 다시 큐에 등록한다. Scheduler가 이 명령을 매분 실행한다. 장기대기 건은 자동 재등록 시 중복 발송 위험이 있으므로 운영자가 `--stale-minutes`를 명시한 경우에만 재큐잉한다. Provider에는 delivery ID 기반 멱등 키를 전달한다.

`idempotency_key`는 모든 문자 배치에서 전역으로 유일하다. 공통 Action은 `purpose`, 템플릿, metadata와 대상 수의 `request_hash`를 저장한다. 동일 키를 같은 요청으로 다시 보내면 기존 배치를 반환하고 Job을 중복 등록하지 않으며, 다른 요청에 같은 키를 사용하면 거부한다.

발송 생성 API는 관리자별 분당 10회, IP별 분당 30회로 제한한다. 한 요청의 병의원은 최대 100개다.

## 5) 상태

배치:

- `PENDING`: 발송대기
- `PROCESSING`: 발송중
- `SENT`: 전건 발송완료
- `PARTIAL_FAILED`: 일부 성공, 일부 실패 또는 제외
- `FAILED`: 성공 건 없이 실패 또는 제외

수신자별 delivery:

- `PENDING`: 발송대기 또는 재시도 대기
- `PROCESSING`: Provider 호출 중
- `SENT`: 발송 성공
- `FAILED`: 최대 재시도 후 최종 실패
- `SKIPPED`: 번호 누락·미인증·형식 오류로 발송 제외

Job은 최대 5회 재시도하고 10초, 30초, 60초, 120초 간격의 backoff를 사용한다.

## 6) Provider 설정

환경변수:

- `SMS_ENABLED`
- `SMS_PROVIDER`
- `SMS_QUEUE`
- `SMS_MAX_BYTES`
- `LMS_MAX_BYTES`

현재 구현 Provider:

- `log`: 로컬 검증용. 실제 통신 없이 앱 로그에 기록하고 성공 처리한다.
- `disabled`: `SMS_ENABLED=false`이거나 지원하지 않는 Provider일 때 사용하며 성공으로 처리하지 않는다.

운영 문자 업체가 선정되면 `SmsProvider` 구현체를 추가하고 `SmsServiceProvider`의 매핑에 등록해야 한다. 운영 환경에서 `log` Provider를 실제 발송으로 사용하면 안 된다.

## 7) Staff API

- `POST /api/v1/staff/hospital-wallets/balance-notices`: 발송 배치 생성
- `GET /api/v1/staff/hospital-wallets/balance-notices`: 발송 배치 목록
- `GET /api/v1/staff/hospital-wallets/balance-notices/{id}`: 수신자별 발송 상세

목록·상세는 `beaulab.hospital_wallet.notice_show`, 발송은 `beaulab.hospital_wallet.notice_send` 권한을 사용한다. 기본 템플릿과 화면용 SMS 기준은 프론트에서 관리하며, 백엔드는 구조화된 메시지 조각과 변수 키를 검증한 뒤 병원별 최종 본문의 LMS 최대 용량을 검증한다.
