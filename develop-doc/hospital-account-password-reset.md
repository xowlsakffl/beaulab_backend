# 병의원 계정 비밀번호 재설정

## 발송

- Staff 병의원 수정 화면의 `비밀번호 재설정 링크`에서 요청한다.
- `POST /api/v1/staff/hospitals/{hospital}/password-reset-link`.
- 병의원 조회 권한과 `beaulab.hospital_account_password_reset.send` 권한이 필요하다.
- 이메일이나 임의 전화번호를 받지 않는다. 연결된 활성 계정의 `verifiedPhone()`만 수신자로 사용한다.
- 광고 안내 담당자 번호와 초대 이메일은 사용하지 않는다.
- 동일 계정은 60초 후 재발송할 수 있다. 재발송 시 기존 링크를 폐기한다.
- 공통 SMS 큐에 적재하며 응답은 발송 접수다. 실제 수신 완료를 의미하지 않는다.

## 재설정

- Hospital Web `/password/reset?token=...`에서 공통 비밀번호 폼을 사용한다.
- `POST /api/v1/hospital/auth/password-reset/verify`로 링크를 검증한다.
- 검증 성공 응답은 `valid`, `hospital_name`, `masked_nickname`만 제공한다. 병의원명과 일부 가린 로그인 아이디를 재설정 폼 상단에 표시한다.
- 아이디는 앞 최대 3글자만 공개하고 나머지는 `*`로 가린다. 짧은 아이디도 마지막 2글자는 가리며, 2글자 이하는 전부 가린다. 원본 아이디는 응답하지 않는다.
- `POST /api/v1/hospital/auth/password-reset`으로 비밀번호와 확인값을 제출한다.
- 64자 난수 토큰을 사용하고 토큰 테이블에는 SHA-256 해시만 저장한다. 기본 만료는 60분이다.
- 계정/병의원 삭제, 계정 비활성화, 인증 전화번호/인증 시각/비밀번호 변경 시 사용할 수 없다.
- 계정 행 잠금 후 토큰을 잠그는 순서로 발송과 재설정을 직렬화한다.
- 비밀번호 변경과 링크 소비, 다른 링크 폐기, 기존 Sanctum 토큰 제거를 한 트랜잭션으로 처리한다.
- 만료/폐기/사용된 링크는 419, 초기 검증 요청 제한은 429 공통 페이지를 사용한다.
- 제출 시 요청 제한과 일시적 오류는 폼 오류로 표시한다.

## 스키마

- `hospital_account_password_resets`는 기존 `0001_01_01_000006_create_account_hospitals_table.php`에서 병의원 계정과 함께 생성한다. 롤백은 재설정 토큰 테이블부터 삭제한다.
- `sms_deliveries.encrypted_message_body`는 기존 `2026_04_10_110500_create_notification_tables.php`의 문자 발송 이력 테이블 정의에 포함한다.
- 기존 DB에는 마이그레이션 파일 수정만으로 스키마 변경이 적용되지 않는다. 데이터가 있는 DB는 테이블·컬럼 반영 여부를 확인하고 누락된 변경만 적용한다.

## 보안 및 운영

- 발송 권한은 역할 이름이 아닌 permission으로 검사한다. 기본 admin/super_admin 권한 정의에 포함한다.
- SMS 링크 본문은 `sms_deliveries.encrypted_message_body`에 암호화하고 일반 본문에는 보안 문자 표시만 남긴다.
- 로그와 API 응답에는 재설정 토큰, 비밀번호, 전체 링크를 기록하지 않는다. 개발용 LogSmsProvider는 테스트 수신 확인용 본문을 기록하므로 운영에서 사용하지 않는다.
- `PASSWORD_RESET_HOSPITAL_URL`은 실제 Hospital Web의 HTTPS 주소로 설정한다.
- 현재 SMS Provider는 log/disabled만 구현되어 있다. 실문자 발송에는 공급자 연동과 sms 큐 워커가 필요하다.
- 배포 시 마이그레이션과 기존 관리자 역할의 새 발송 권한 반영 후 SMS 큐 워커를 재시작한다. 이전 워커는 암호화 본문을 해독하는 변경을 읽지 못한다.
