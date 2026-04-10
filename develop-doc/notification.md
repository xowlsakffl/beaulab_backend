# 알림 설계

이 문서는 현재 기준의 알림 기능 설계를 정리한다.  
기준일은 `2026-04-10`이며, 현재 스코프는 `채팅 + 댓글 등 공통 도메인 알림`이다.

## 1. 현재 범위

- 구현 대상
  - 인앱 알림
  - 모바일 푸시 알림
  - 이벤트별 알림 on/off
  - 집계형 알림
- 적용 대상
  - 채팅 메시지 알림
  - 댓글 알림
  - 향후 다른 도메인 이벤트

다른 도메인에도 붙일 수 있는 공용 구조로 유지한다.

## 2. 기술 선택

- 알림 원장 저장: 관계형 DB
- 비동기 처리: Redis + Horizon
- 실시간 인앱 전달: WebSocket 계층
- 앱 오프라인 전달: FCM / APNs

현재는 별도 알림 서비스나 Kafka를 도입하지 않는다.  
현 스코프에서는 Laravel 내부 공용 모듈로 두는 편이 더 실용적이다.

## 3. 핵심 원칙

### 3.1 알림은 원시 이벤트를 그대로 쌓지 않는다

댓글 알림처럼 같은 대상에서 반복적으로 발생하는 이벤트는 `"외 N건"` 형태로 묶어야 한다.

- 잘못된 방식
  - 댓글 9건이면 알림 9건 insert
- 현재 방식
  - 같은 수신자/같은 대상/같은 이벤트는 집계 갱신

### 3.2 읽지 않은 집계 버킷은 수신자 기준 1개만 유지한다

이를 위해 `notification_inboxes`에 두 키를 둔다.

- `aggregation_key`
  - 같은 알림 묶음 판단용 논리 키
- `open_aggregation_key`
  - 읽지 않은 집계 버킷 유니크 잠금 키

읽지 않은 묶음이 있으면 새 row를 만들지 않고 기존 row를 갱신한다.

### 3.3 알림 정책은 도메인별로 다르게 가져간다

- 댓글 알림
  - 적극 집계
- 채팅 알림
  - 실시간 우선
  - 필요 시 `chat_id` 기준 제한적 집계

즉, 모든 이벤트를 같은 방식으로 묶으면 안 된다.

## 4. 테이블 구조

### 4.1 notification_inboxes

도메인 공통 인앱 알림함이다.

- `recipient_type`, `recipient_id`
  - 누가 받는 알림인지
- `actor_type`, `actor_id`
  - 누가 발생시켰는지
- `event_type`
  - 예: `chat.message.created`, `talk.comment.created`
- `title`, `body`
  - 사용자 표시용 텍스트
- `aggregation_key`
  - 같은 알림 묶음 판단용
- `open_aggregation_key`
  - 읽지 않은 집계 row 유니크 제약용
- `event_count`
  - 집계된 이벤트 수
- `target_type`, `target_id`
  - 어떤 엔티티에 대한 알림인지
- `payload`
  - 부가 정보
- `read_at`
  - 읽음 처리 시각

관련 파일: `database/migrations/2026_04_10_110500_create_notification_tables.php`

### 4.2 notification_deliveries

채널별 발송 이력이다.

- `channel`
  - `IN_APP`, `PUSH`, `EMAIL`, `WEB`
- `status`
  - `PENDING`, `SENT`, `FAILED`
- `provider`
  - 예: `FCM`, `APNS`, `REVERB`

이 테이블은 발송 성공/실패 추적과 재시도 분석 용도다.

### 4.3 notification_devices

푸시 토큰과 브라우저 엔드포인트를 저장한다.

- `owner_type`, `owner_id`
- `platform`
- `push_token`
- `last_seen_at`
- `revoked_at`

### 4.4 notification_preferences

이벤트별 알림 채널 on/off 설정이다.

- `event_type`
- `in_app`
- `push`
- `email`

## 5. 서비스 로직 불변식

아래 규칙은 서비스 계층에서 강제해야 한다.

1. 집계 대상 이벤트는 `aggregation_key`를 일관되게 생성한다.
2. 읽지 않은 동일 묶음이 있으면 새 row insert 대신 기존 row update를 시도한다.
3. 알림 읽음 처리 시 `open_aggregation_key`를 `null`로 바꾼다.
4. Push 발송 대상은 `notification_preferences`와 `notification_devices`를 함께 확인한다.
5. revoke 된 디바이스 토큰에는 발송하지 않는다.

## 6. 처리 흐름

### 6.1 댓글 알림

1. 댓글 생성 이벤트 발생
2. 수신자 계산
3. `aggregation_key` 생성
4. 같은 unread 알림 row 조회 또는 유니크 키 기반 갱신
5. `event_count` 증가
6. 인앱 알림 갱신
7. 필요 시 푸시 발송

### 6.2 채팅 알림

1. 채팅 메시지 저장 완료
2. 상대 유저의 채팅방 알림 설정 확인
3. 접속 중이면 실시간 전달 우선
4. 미접속 또는 백그라운드 상태면 Push 발송
5. 필요 시 `chat_id` 기준으로 제한적 집계

## 7. 예시

### 7.1 댓글 알림 집계

- 이벤트
  - `talk.comment.created`
- 대상
  - `talk:123`
- 수신자
  - `user:55`
- 집계 키 예시
  - `recipient:user:55:event:talk.comment.created:target:talk:123`

이 경우 같은 글에 새 댓글이 계속 달리면 unread 알림 1건만 유지하고 `event_count`만 증가시킨다.

### 7.2 채팅 알림

- 이벤트
  - `chat.message.created`
- 대상
  - `chat:88`
- 수신자
  - `user:55`

채팅은 일반 댓글과 달리 즉시성이 더 중요하므로 무제한 합치지 않는다.

## 8. 현재 구조가 타당한 이유

지금 필요한 건 채팅 알림만 처리하는 임시 구조가 아니다.  
댓글 같은 다른 도메인에도 바로 붙을 수 있는 공용 알림 모듈이 필요하다.

지금 구조의 장점:

- 채팅 외 다른 도메인에도 재사용 가능하다
- 집계형 알림 요구를 수용할 수 있다
- 인앱, 푸시, 이메일 이력을 분리해 운영 추적이 쉽다
- 나중에 정책이 늘어나도 채팅 도메인 코드를 오염시키지 않는다
