# API 응답 / 페이지네이션 규칙

작성 기준: 2026-05-18

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

## 2) LengthAware pagination

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

## 3) 빈 페이지 fallback

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

## 4) Cursor pagination 예외

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

## 5) 현재 통일된 목록

다음 계열은 `PaginatedResponse::fromPaginator()`를 사용한다.

- Staff 일반 목록: 회원, 병원, 뷰티, 의사, 전문가, 영상, 공지, FAQ, 카테고리, 해시태그
- Staff 게시물 목록: 토크, 토크 댓글, 병의원 후기, 후기 댓글, 병의원 평가
- Staff 상세 보조 목록: 댓글, 히스토리, 신고내역
- Staff 신고게시물 목록
- User 목록: 채팅방, 알림, 차단 회원

직접 `current_page`, `per_page`, `total`, `last_page`를 조립하지 않는다. 예외가 필요하면 먼저 cursor pagination인지, 외부 API 응답인지 명확히 구분한다.
