# 채팅 설계

이 문서는 현재 기준의 채팅 기능 설계를 정리한다.  
기준일은 `2026-04-10`이며, 현재 스코프는 `앱 유저간 1:1 채팅`이다.

## 1. 현재 범위

- 구현 대상
  - `account_users` 간 1:1 채팅
  - 텍스트/이미지/파일 메시지 발송
  - 채팅방별 알림 on/off
  - 읽음 상태
  - 읽음 상태 실시간 전달
  - 모바일 앱 기준 실시간 메시지 전달

## 2. 기술 선택

- 백엔드: Laravel
- 영속 저장: MySQL
- 비동기 처리: Redis + Horizon
- 실시간 전달: Laravel Reverb
- 향후 파일 첨부: 기존 공용 `Media` 재사용

현재는 `NoSQL`, `Kafka`, `별도 채팅 마이크로서비스`, `HTTP Polling 주력`으로 가지 않는다.  
이 스코프에서는 복잡도만 늘고 실익이 적다.

## 3. 핵심 원칙

### 3.1 메시지는 항상 DB에 먼저 저장한다

실시간 전송보다 메시지 저장이 우선이다.

1. 메시지 DB 저장
2. 채팅방 마지막 메시지 갱신
3. 큐 Job 발행
4. WebSocket 전달 또는 후속 처리

### 3.2 유저 1:1 채팅은 유저쌍당 채팅방 1개만 유지한다

중복 채팅방 생성을 막기 위해 `chats.match_key`를 사용한다.

- 형식 예시: `12:48`
- 규칙: 두 사용자 ID를 정렬한 뒤 `min:max` 문자열로 저장
- 제약: `unique`

따라서 같은 두 사용자는 동시에 두 개의 채팅방을 가질 수 없다.

## 4. 테이블 구조

### 4.1 chats

채팅방 헤더 테이블이다.

- `status`
  - `ACTIVE`, `SUSPENDED`, `CLOSED`
- `match_key`
  - 정렬된 두 사용자 ID 기반 유니크 키
- `created_by_user_id`
  - 채팅 생성 사용자
- `last_message_id`, `last_message_at`
  - 목록 조회 성능을 위한 역정규화 값
- `closed_at`
  - 종료 시각

관련 파일: `database/migrations/2026_04_10_110000_create_chats_table.php`

### 4.2 chat_participants

채팅방 참여자 및 개인 상태 테이블이다.

- `chat_id`
- `account_user_id`
- `last_read_message_id`
- `last_read_at`
- `notifications_enabled`

참여자는 현재 스코프상 정확히 2명이어야 한다.  
이 규칙은 DB만으로 완전하게 막기 어렵기 때문에 서비스 로직에서 강제한다.

관련 파일: `database/migrations/2026_04_10_110100_create_chat_participants_table.php`

### 4.3 chat_messages

실제 메시지 저장 테이블이다.

- `chat_id`
- `sender_user_id`
- `client_message_id`
  - 앱 재전송 시 중복 저장 방지용 멱등 키
- `message_type`
  - `TEXT`, `IMAGE`, `FILE`
  - `IMAGE`, `FILE` 첨부는 공용 `Media` 테이블을 `ChatMessage`에 연결한다
- `body`
- `reply_to_message_id`
- `metadata`

관련 파일: `database/migrations/2026_04_10_110200_create_chat_messages_table.php`

## 5. 서비스 로직 불변식

아래 규칙은 서비스 계층에서 강제해야 한다.

1. 채팅 생성 시 participant는 정확히 2명만 생성한다.
2. participant 2명은 서로 다른 사용자여야 한다.
3. 메시지 발신자는 해당 채팅의 participant여야 한다.
4. `last_message_id`는 같은 `chat_id`의 메시지여야 한다.
5. `last_read_message_id`는 같은 `chat_id`의 메시지여야 한다.
6. 채팅 종료 후 다시 대화할 때는 새 채팅방을 만들지 않고 기존 채팅방을 재활성화한다.

## 6. 처리 흐름

### 6.1 채팅방 생성

1. 두 유저 ID를 정렬해 `match_key` 생성
2. 같은 `match_key` 채팅방 조회
3. 있으면 재사용 또는 재활성화
4. 없으면 `chats` 생성
5. `chat_participants` 2건 생성

### 6.2 메시지 발송

1. 발신자가 participant인지 검증
2. `chat_messages` 저장
3. 이미지/파일 메시지면 공용 `media`에 첨부 저장
4. `chats.last_message_id`, `last_message_at` 갱신
5. 큐 Job 발행
6. 접속 중이면 WebSocket 전달

### 6.3 읽음 처리

1. 사용자가 participant인지 검증
2. `chat_participants.last_read_message_id`, `last_read_at` 갱신
3. `chat.read.updated` 이벤트로 클라이언트에 읽음 상태 전송

## 7. User API

- `GET /api/v1/user/chats`
  - 내 채팅방 목록
- `POST /api/v1/user/chats`
  - 상대 사용자와 1:1 채팅방 생성 또는 재활성화
- `GET /api/v1/user/chats/{chat}/messages`
  - 메시지 목록
- `POST /api/v1/user/chats/{chat}/messages`
  - 텍스트 메시지 발송
- `POST /api/v1/user/chats/{chat}/read`
  - 읽음 처리
- `PUT|PATCH /api/v1/user/chats/{chat}/notifications`
  - 채팅방별 알림 on/off
- `DELETE /api/v1/user/chats/{chat}`
  - 채팅 종료

## 8. 실시간 채널

- 브로드캐스트 인증
  - `GET|POST /broadcasting/auth`
  - 미들웨어: `api`, `auth:sanctum`, `abilities:actor:user`
- 채팅방 채널
  - `private-chat.{chatId}`
  - 해당 채팅방 participant만 구독 가능
- 메시지 이벤트
  - 이벤트명: `.chat.message.created`
  - payload의 `message`에는 `is_mine`을 넣지 않는다.
  - 앱은 `sender_user_id`와 현재 로그인 유저 ID를 비교해서 내 메시지 여부를 판단한다.
- 읽음 이벤트
  - 이벤트명: `.chat.read.updated`
  - payload: `chat_id`, `reader_user_id`, `last_read_message_id`, `last_read_at`

## 9. 현재 구현 상태

- 채팅방 생성/목록/메시지 조회/메시지 발송/읽음 처리/알림 on-off/채팅 종료 API 구현 완료
- 메시지 발송 후 Reverb 브로드캐스트 이벤트 발행
- 읽음 처리 후 Reverb 브로드캐스트 이벤트 발행
- 이미지/파일 메시지는 공용 `Media` 테이블에 첨부 저장
- `client_message_id` 재시도 시 기존 메시지를 반환하고 브로드캐스트/알림은 중복 발행하지 않음
- 상대 participant의 `notifications_enabled`가 켜져 있으면 공통 알림 모듈에 `chat.message.created` 알림 생성

## 10. 향후 확장 포인트

병원계정 ↔ 직원관리자 채팅이 실제 구현 대상이 되면 그때 아래를 추가한다.

- `support_chats` 같은 확장 테이블
- 담당자 배정
- 운영 메모 연결
- 상태 전이 세분화
- 웹 전용 실시간 정책
