# 채팅 설계

이 문서는 현재 기준의 채팅 기능 설계를 정리한다.  
기준일은 `2026-04-14`이며, 현재 스코프는 `앱 유저간 1:1 채팅`이다.

## 1. 현재 범위

- 구현 대상
  - `account_users` 간 1:1 채팅
  - 텍스트/이미지/파일 메시지 발송
  - 채팅방별 알림 on/off
  - 읽음 상태
  - 읽음 상태 실시간 전달
  - 모바일 앱 기준 실시간 메시지 전달
  - 사용자 차단

## 2. 기술 선택

- 백엔드: Laravel
- 영속 저장: MySQL
- 비동기 처리: Redis + Horizon
- 실시간 전달: Laravel Reverb
- 파일 첨부: 기존 공용 `Media` 재사용

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

### 3.3 채팅방은 첫 메시지를 보낼 때 생성한다

앱에서 상대와의 채팅 UI에 진입하는 행위만으로는 서버에 채팅방을 만들지 않는다.

- 상대 선택 또는 채팅 UI 진입: 클라이언트 로컬 작성 상태
- 첫 메시지 전송: `chats`, `chat_participants`, `chat_messages`를 하나의 트랜잭션으로 생성
- 메시지를 보내지 않고 나감: 서버 데이터 없음, 상대방 채팅 목록에도 노출 없음
- 채팅 목록 조회: `last_message_id`가 있는 채팅만 노출

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
- `deleted_until_message_id`
  - 사용자별 채팅 삭제 기준 메시지 ID
  - 이 ID 이하 메시지는 해당 사용자에게만 보이지 않는다
- `deleted_at`
  - 사용자별 채팅 삭제 시각
  - 참여자 행 삭제나 채팅방 전체 종료가 아니다

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

### 4.4 account_user_blocks

앱 사용자 간 방향성 차단 관계 테이블이다.

- `blocker_user_id`
  - 차단한 사용자
- `blocked_user_id`
  - 차단된 사용자
- `blocked_at`
  - 차단 시각

차단은 방향성이 있다. A가 B를 차단해도 B가 A를 차단한 상태가 자동 생성되지는 않는다.
단, 메시지 발송은 어느 한쪽이라도 차단 관계가 있으면 막는다.

관련 파일: `database/migrations/2026_04_10_110600_create_account_user_blocks_table.php`

## 5. 서비스 로직 불변식

아래 규칙은 서비스 계층에서 강제해야 한다.

1. 첫 메시지 전송으로 채팅 생성 시 participant는 정확히 2명만 생성한다.
2. participant 2명은 서로 다른 사용자여야 한다.
3. 메시지 발신자는 해당 채팅의 participant여야 한다.
4. `last_message_id`는 같은 `chat_id`의 메시지여야 한다.
5. `last_read_message_id`는 같은 `chat_id`의 메시지여야 한다.
6. 사용자가 채팅을 삭제해도 `chats.status`는 바꾸지 않는다.
7. 사용자별 삭제 후 메시지 목록은 `deleted_until_message_id` 이후 메시지만 보여준다.
8. 사용자별 삭제 후 새 메시지가 오거나 보내지면 같은 채팅방이 다시 목록에 노출된다.
9. 메시지 저장 전 발신자와 상대방 사이의 차단 관계를 확인한다.
10. 발신자가 상대를 차단한 경우 `차단 해제 후 메시지를 보낼 수 있습니다.`로 막는다.
11. 상대가 발신자를 차단한 경우 `메시지를 보낼 수 없습니다.`로 막아 차단 사실을 직접 노출하지 않는다.
12. 차단으로 막힌 메시지는 저장하지 않으며, 따라서 알림도 생성하지 않는다.

## 6. 처리 흐름

### 6.1 첫 메시지로 채팅 생성

1. 클라이언트가 `peer_user_id`, `message_type`, `body` 또는 `attachments`를 보낸다.
2. 서버가 두 유저 ID를 정렬해 `match_key`를 만든다.
3. 같은 `match_key` 채팅방을 잠금 조회한다.
4. 없으면 `chats`와 `chat_participants` 2건을 생성한다.
5. 있으면 재사용 또는 재활성화한다.
6. 같은 트랜잭션에서 `chat_messages`를 저장한다.
7. `chats.last_message_id`, `last_message_at`을 갱신한다.
8. 커밋 후 브로드캐스트와 공통 알림 생성을 연결한다.

### 6.2 메시지 발송

기존 채팅방에 메시지를 추가할 때의 흐름이다.

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

### 6.4 채팅 삭제

사용자 앱에서의 채팅 삭제는 카카오톡식 사용자별 삭제로 처리한다. 한 명이 삭제해도 상대방의 채팅방과 메시지는 유지된다.

1. 사용자가 participant인지 검증
2. 현재 `chats.last_message_id`를 `chat_participants.deleted_until_message_id`에 저장
3. `chat_participants.deleted_at` 저장
4. 삭제 시점까지는 읽은 것으로 보고 `last_read_message_id`, `last_read_at` 갱신
5. 내 채팅 목록에서는 숨김
6. 이후 새 메시지가 생기면 같은 채팅방이 다시 내 목록에 노출되고, 메시지 목록은 삭제 기준 이후 메시지만 조회된다

### 6.5 사용자 차단

차단은 채팅방 상태가 아니라 사용자 관계 상태로 처리한다.

1. 사용자가 `blocked_user_id`를 보내 차단을 요청한다.
2. 서버가 `account_user_blocks`에 `blocker_user_id`, `blocked_user_id` 방향으로 저장한다.
3. 기존 1:1 채팅방이 있으면 차단한 사람의 `chat_participants.deleted_until_message_id`, `deleted_at`만 갱신해 내 목록에서 숨긴다.
4. 상대방의 채팅방과 메시지는 삭제하지 않는다.
5. 이후 두 사용자 사이의 첫 메시지/기존 채팅 메시지 발송은 양방향 차단 관계를 확인한 뒤 저장 전에 거부한다.
6. 메시지가 저장되지 않으므로 Reverb 브로드캐스트와 공통 알림 생성도 발생하지 않는다.

## 7. User API

- `GET /api/v1/user/chats`
  - 내 채팅방 목록
- `POST /api/v1/user/chats/messages`
  - 채팅방 ID 없이 첫 메시지 전송
  - 이 요청에서 채팅방이 없으면 생성하고, 있으면 기존 채팅방에 메시지를 저장한다
- `GET /api/v1/user/chats/{chat}/messages`
  - 메시지 목록
- `POST /api/v1/user/chats/{chat}/messages`
  - 기존 채팅방에 메시지 발송
- `POST /api/v1/user/chats/{chat}/read`
  - 읽음 처리
- `PUT|PATCH /api/v1/user/chats/{chat}/notifications`
  - 채팅방별 알림 on/off
- `DELETE /api/v1/user/chats/{chat}`
  - 내 채팅방 삭제
  - 채팅방 전체 종료가 아니라 현재 사용자에게만 목록/이전 메시지를 숨긴다
- `GET /api/v1/user/blocks`
  - 내가 차단한 사용자 목록
- `POST /api/v1/user/blocks`
  - 사용자 차단
  - body: `blocked_user_id`
  - 기존 채팅방이 있으면 내 목록에서 숨긴다
- `DELETE /api/v1/user/blocks/{blockedUserId}`
  - 사용자 차단 해제

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

- 첫 메시지 기반 채팅 생성/목록/메시지 조회/메시지 발송/읽음 처리/알림 on-off/사용자별 채팅 삭제 API 구현 완료
- 앱 공개 API에서는 빈 채팅방 생성 엔드포인트를 제거하고, 첫 메시지 전송 API로만 채팅방을 생성
- 채팅 목록은 `last_message_id`가 있는 채팅방만 노출
- 채팅 삭제는 `chat_participants.deleted_until_message_id` 기준으로 현재 사용자에게만 적용
- 메시지 발송 후 Reverb 브로드캐스트 이벤트 발행
- 읽음 처리 후 Reverb 브로드캐스트 이벤트 발행
- 이미지/파일 메시지는 공용 `Media` 테이블에 첨부 저장
- `client_message_id` 재시도 시 기존 메시지를 반환하고 브로드캐스트/알림은 중복 발행하지 않음
- 상대 participant의 `notifications_enabled`가 켜져 있으면 공통 알림 모듈에 `chat.message.created` 알림 생성
- 사용자 차단 API 구현 완료
- 차단 관계가 있으면 첫 메시지/기존 채팅 메시지 저장 전 거부
- 차단 시 차단한 사용자에게만 기존 채팅방 숨김 처리

## 10. 향후 확장 포인트

병원계정 ↔ 직원관리자 채팅이 실제 구현 대상이 되면 그때 아래를 추가한다.

- `support_chats` 같은 확장 테이블
- 담당자 배정
- 운영 메모 연결
- 상태 전이 세분화
- 웹 전용 실시간 정책
