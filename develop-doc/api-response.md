# API 응답 / 페이지네이션 규칙

작성 기준: 2026-06-23

이 문서는 현재 코드 기준의 API 응답 포맷과 페이지네이션 헬퍼 사용 규칙을 정리한다.

## 1) 기본 응답

컨트롤러는 `ApiResponse::success($data, $meta)`를 통해 응답한다.

목록 응답은 일반적으로 다음 구조를 가진다.

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 0,
    "last_page": 1
  }
}
```

에러 응답은 공통 예외 핸들러가 만든다. 상세 규칙은 `error-handling.md`를 참고한다.

## 2) User API 응답 정책

User API는 조회 API와 행위 처리 API의 응답 책임을 분리한다.

### 조회 API

리소스를 화면에 표시하는 API는 DTO를 사용한다.

- `GET list`
- `GET detail`
- 채팅방 목록, 채팅 메시지 목록
- 알림 목록, 차단 회원 목록
- 토크/후기 상세처럼 앱 화면을 구성하는 조회 응답

조회 응답은 Eloquent 모델을 그대로 노출하지 않고 `Dto\User` 계열에서 필요한 필드만 변환한다.

```php
return [
    'talk' => TalkForUserDetailDto::fromModel($talk)->toArray(),
];
```

### 생성 API

생성 직후 클라이언트가 화면에 바로 붙여야 하는 리소스는 DTO를 반환할 수 있다.

- 토크 생성
- 토크 댓글 생성
- 병의원 후기 생성
- 병의원 후기 댓글 생성
- 채팅 메시지 전송

단, 클라이언트가 생성 결과 전체를 즉시 사용하지 않는 경우에는 `id` 또는 `message` 같은 최소 응답만 내려준다.

### Command API

단순 행위 처리 API는 내부 상태나 DTO를 내려주지 않는다.

- 신고
- 삭제
- 읽음 처리
- 차단/차단 해제
- 알림 전체 읽음
- 토글/상태 변경성 API

기본 응답은 컨트롤러에서 직접 명시한다.

```php
return ApiResponse::success([
    'message' => '신고가 완료되었습니다.',
]);
```

Action은 처리만 담당하고, 단순 완료 메시지를 만들지 않는다. 단, command 이후 클라이언트 동기화에 최소 상태가 필요한 경우에만 `message + 최소 필드`를 허용한다.

```php
return ApiResponse::success([
    'message' => '알림 설정이 변경되었습니다.',
    'notifications_enabled' => true,
]);
```

### 응답 계약 기준

컨트롤러가 다음처럼 방어적으로 응답 키를 고르는 구조는 지양한다.

```php
return ApiResponse::success($result['talk'] ?? $result);
return ApiResponse::success($result['chat'] ?? $result);
```

이 패턴은 Action의 응답 계약이 불명확하다는 신호다. 신규 코드에서는 Action 반환 구조를 고정하고 컨트롤러가 그대로 연결하거나, command API라면 컨트롤러에서 완료 메시지를 직접 내려준다.

현재 기존 코드에는 생성/삭제/읽음 처리 계열에 DTO 반환과 메시지 반환이 섞여 있다. 신규 구현은 위 기준을 따르고, 기존 코드는 기능 수정 시 함께 정리한다.

## 3) LengthAware pagination

Laravel `LengthAwarePaginator`를 쓰는 목록 응답은 `App\Common\Support\PaginatedResponse`를 사용한다.

```php
return PaginatedResponse::fromPaginator(
    $paginator,
    fn (Model $model): array => Dto::fromModel($model)->toArray(),
);
```

`fromPaginator()`는 `items`와 `meta`를 만든다. 추가 meta가 필요하면 세 번째 인자로 전달한다.

```php
return PaginatedResponse::fromPaginator(
    $paginator,
    $mapper,
    ['summary' => $summary],
);
```

현재 신고게시물 목록은 `summary`를 추가 meta로 내려준다.

## 4) 빈 페이지 fallback

상세 화면의 댓글, 히스토리, 신고내역처럼 페이지 삭제/필터 변경으로 현재 페이지가 비어질 수 있는 목록은 `paginateWithFallback()`을 사용한다.

```php
$paginator = PaginatedResponse::paginateWithFallback(
    queryFactory: fn () => $query->builder($model),
    perPage: 10,
    pageName: 'page',
    page: $page,
);
```

현재 페이지가 1보다 크고 결과가 비어 있으면 1페이지 결과를 반환한다. 프론트가 삭제/상태 변경 후 빈 페이지에 멈추는 현상을 줄이기 위한 규칙이다.

## 5) Cursor pagination 예외

채팅 메시지 목록은 `ChatMessageListForUserQuery`에서 cursor 방식으로 조회한다.

```json
{
  "per_page": 30,
  "has_more": true,
  "after_id": null,
  "before_id": 123,
  "order": "desc"
}
```

이 응답은 `current_page`, `total`, `last_page` 개념이 없으므로 `PaginatedResponse`에 태우지 않는다. cursor 방식 목록을 추가할 때는 이 구조를 명시적으로 유지한다.

## 6) 현재 통일된 목록

다음 계열은 `PaginatedResponse::fromPaginator()`를 사용한다.

- Staff 일반 목록: 회원, 병원, 입점신청, 뷰티, 의사, 전문가, 영상, 공지, FAQ, 카테고리, 해시태그
- Staff 게시물 목록: 토크, 토크 댓글, 병의원 후기, 후기 댓글, 병의원 평가
- Staff 상세 보조 목록: 댓글, 히스토리, 신고내역
- Staff 신고게시물 목록
- User 목록: 채팅방, 알림, 차단 회원

직접 `current_page`, `per_page`, `total`, `last_page`를 조립하지 않는다. 예외가 필요하면 먼저 cursor pagination인지, 외부 API 응답인지 명확히 구분한다.
