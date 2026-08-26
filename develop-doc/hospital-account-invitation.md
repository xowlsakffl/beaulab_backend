# 병의원 계정 초대와 생성

작성 기준: 2026-08-25

이 문서는 Staff가 병의원 계정 생성 링크를 발송하고, 수신자가 휴대폰 문자 인증 후 `AccountHospital`을 생성하는 기준을 정리한다.

## 1. 핵심 정책

- `Hospital`과 `AccountHospital`은 1:1이다. DB의 `account_hospitals.hospital_id` unique 제약이 최종 기준이다.
- `AccountHospital`은 이메일을 저장하지 않는다. 로그인 식별자는 `nickname`이다.
- 초대 수신 이메일은 `hospital_account_invitations.recipient_email`에 초대 이력으로만 저장한다.
- 계정 생성 폼은 아이디, 비밀번호, 휴대폰 번호와 문자 인증번호만 받는다.
- `AccountHospital.name`은 사용자 실명이 아니다. 호환을 위해 연결된 `Hospital.name`을 복제해 저장하고 로그인·검색·담당자 판정에는 사용하지 않는다.
- 병의원 계정이 남긴 운영 히스토리와 충전금 처리자명은 복제된 `AccountHospital.name`이 아니라 관계의 최신 `Hospital.name`을 사용한다.
- 인증된 계정 연락처는 `phone_verified_at`이 기록된 계정 전화번호만 노출한다. 계정이 없거나 인증되지 않은 번호는 빈 값으로 취급한다.
- 신규 병의원의 검수 상태는 `NOT_APPLIED`(미신청), 운영 상태는 `ACTIVE`다.

## 2. 공통 초대 API

직접 생성 병의원과 입점신청은 같은 Staff API를 사용한다.

| 메서드 | 경로 | 용도 |
|---|---|---|
| `GET` | `/api/v1/staff/hospital-account-invitations` | 원본별 초대 발송 내역 페이지네이션 조회 |
| `POST` | `/api/v1/staff/hospital-account-invitations` | 초대 생성 및 메일 큐 등록 |
| `DELETE` | `/api/v1/staff/hospital-account-invitations/{id}` | 미사용 초대 폐기 |

원본은 `source_type`과 `source_id`로 지정한다.

조회 API는 `page`, `per_page`를 지원하며 최신 발송순으로 `data`와 페이지네이션 `meta`를 반환한다. 기본 `per_page`는 5, 최댓값은 20이다.

초대 상태는 별도 상태 컬럼을 중복 저장하지 않고 `used_at`, `revoked_at`, `expires_at`으로 계산한다.

| 저장값 | 표시명 | 기준 |
|---|---|---|
| `ACTIVE` | 사용 가능 | 미사용·미폐기이며 만료 전 |
| `USED` | 사용 완료 | 계정 생성으로 `used_at` 기록 |
| `REVOKED` | 폐기 | 재전송 또는 Staff 폐기로 `revoked_at` 기록 |
| `EXPIRED` | 만료 | 미사용·미폐기이며 만료시각 경과 |

재전송은 아직 유효한 기존 초대만 폐기한다. 이미 만료된 이력은 `만료` 상태를 유지한다.

- `HOSPITAL`: Staff가 직접 만든 기존 병의원
- `HOSPITAL_ENTRY`: 승인된 입점신청

입점신청 화면은 `applicant_email`을 초깃값으로 사용할 수 있지만 Staff가 수정할 수 있다. 백엔드는 신청자 이메일 사용을 강제하지 않는다.

권한은 병의원/입점신청 수정 권한과 별개로 아래를 사용한다.

- 조회: `beaulab.hospital_account_invitation.show`
- 발송/폐기: `beaulab.hospital_account_invitation.update`

초대 권한만으로 다른 업무 영역의 병의원이나 입점신청에 접근할 수는 없다. 목록·발송·폐기에는 초대 권한과 해당 원본의 조회 권한이 함께 필요하다.

## 3. 링크 검증과 계정 생성 API

| 메서드 | 경로 | 용도 |
|---|---|---|
| `GET` | `/api/v1/hospital/auth/account-invitations/{token}` | 링크 유효성 및 병의원명 확인 |
| `POST` | `/api/v1/hospital/auth/account-invitations/{token}/phone-verifications` | 휴대폰 인증번호 발송 |
| `POST` | `/api/v1/hospital/auth/account-invitations/{token}/phone-verifications/{id}/verify` | 인증번호 확인 및 완료 증표 발급 |
| `POST` | `/api/v1/hospital/auth/account-invitations/{token}` | 계정 생성 완료 |

공개 조회 응답은 병의원명과 만료시각만 반환한다. 초대 수신 이메일, 원본 ID, 토큰 해시는 노출하지 않는다.

초대 토큰은 원문을 저장하지 않고 SHA-256 해시만 저장한다. 메일 큐 payload도 암호화한다. 기본 만료시간은 72시간이며 사용 또는 폐기된 토큰은 재사용할 수 없다.

## 4. 휴대폰 문자 인증

문자 인증은 휴대폰 번호의 현재 점유만 확인하며 실명확인이 아니다. 인증번호 발송과 결과 이력은 공통 `Sms` 도메인과 Redis `sms` 큐를 사용한다.

- 인증번호는 6자리 숫자이며 DB에는 단방향 해시만 저장한다.
- 인증번호 기본 유효시간은 5분, 재발송 간격은 60초, 최대 오입력은 5회이며 인증 성공 즉시 재사용할 수 없다.
- 새 인증번호를 발송하면 같은 초대의 기존 미사용 인증은 즉시 무효화한다.
- 인증 성공 시 특정 초대와 전화번호에 귀속된 64자리 일회용 증표를 발급한다.
- 원문 증표는 응답 시 한 번만 반환하고 DB에는 SHA-256 해시만 저장한다.
- 완료 증표 기본 유효시간은 15분이며 계정 생성 성공 시 `consumed_at`을 기록한다.
- 발송 Provider 상태와 최종 본문은 `sms_deliveries`, 인증 상태는 `hospital_account_phone_verifications`가 각각 소유한다.
- 발송은 초대·전화번호 기준 분당 2회/시간당 5회, IP 기준 시간당 20회로 제한한다.

## 5. 완료 트랜잭션

### 직접 생성 병의원

1. 초대와 휴대폰 인증 증표를 row lock으로 재검증한다.
2. 병의원에 계정이 연결되어 있지 않은지 확인한다.
3. 인증 전화번호를 병의원 광고 안내 수신 번호 1과 계정 전화번호에 반영한다.
4. 활성 `AccountHospital`을 만들고 `hospital.owner` 역할을 부여한다.
5. 휴대폰 인증 증표와 초대를 사용 완료 처리한다.

### 입점신청 병의원

1. 입점신청이 `APPROVED`이고 아직 전환되지 않았는지 다시 확인한다.
2. 병의원명과 사업자등록번호 중복을 다시 확인한다.
3. `Hospital`, `HospitalBusinessRegistration`, 0P `HospitalWallet`을 생성한다.
4. 입점신청 사업자등록증은 사업자등록정보의 `business_registration_file`로 이전한다.
5. 의사면허번호와 의사면허증은 입점신청에 유지하며 의료진을 별도로 생성할 때 사용한다.
6. `AccountHospital`을 생성하고 초대·휴대폰 인증·입점신청을 완료 처리한다.

위 작업은 하나의 DB 트랜잭션으로 처리한다. 중간 단계가 실패하면 병의원, 사업자정보, 파일 소유권, 지갑, 계정 변경을 모두 롤백한다.

## 6. 운영과 환경변수

```dotenv
HOSPITAL_ACCOUNT_INVITATION_URL=http://localhost:3002/account/create
HOSPITAL_ACCOUNT_INVITATION_EXPIRE_HOURS=72
HOSPITAL_ACCOUNT_PHONE_CODE_TTL_MINUTES=5
HOSPITAL_ACCOUNT_PHONE_VERIFICATION_TTL_MINUTES=15
HOSPITAL_ACCOUNT_PHONE_RESEND_SECONDS=60
HOSPITAL_ACCOUNT_PHONE_MAX_ATTEMPTS=5
HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE_CONNECTION=redis
HOSPITAL_ACCOUNT_INVITATION_MAIL_QUEUE=mail
```

초대 메일은 Redis `mail` 큐, 인증문자는 Redis `sms` 큐를 사용한다. 로컬에서는 Mailpit과 Horizon 또는 `queue:work --queue=mail,sms`가 실행 중이어야 한다. `SMS_PROVIDER=log` 환경에서는 실제 문자를 보내지 않고 `storage/logs/laravel.log`에 인증번호가 포함된 발송 내용을 기록한다.

초대 링크는 `apps/hospital-web`의 `/account/create` 화면으로 연결한다. 화면은 링크를 API로 먼저 검증하고 `phone_verification_token`이 발급된 경우에만 계정 생성 요청을 보낸다.

입점신청, 계정 초대, 휴대폰 인증은 각각 독립 migration으로 관리한다. 기존 개발 DB는 스키마 변경을 반영하려면 `migrate:fresh`가 필요하다. 공유/운영 환경에 최초 배포된 뒤부터는 기존 migration을 수정하지 않고 추가 migration으로 변경한다.
