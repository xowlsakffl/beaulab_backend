# Cache / Redis 적용 규칙

작성 기준: 2026-07-21

이 문서는 Redis Cache 적용 기준과 Staff 관리자 summary 캐시 규칙을 정리한다. Redis는 원본 저장소가 아니라 조회 성능을 위한 복사본이며, DB가 최종 기준이다.

## 1) 기본 원칙

- 돈, 승인상태, 광고 구좌, 신고 처리 상태 같은 원본 데이터는 DB transaction과 제약 조건을 기준으로 확정한다.
- Redis Cache는 반복 조회 비용을 낮추는 용도로만 사용한다.
- 캐시 장애가 API 장애로 번지면 안 되는 조회성 데이터는 DB 조회로 fallback한다.
- 데이터 변경 후에는 관련 캐시를 삭제하고, 다음 조회에서 다시 채우는 방식을 기본으로 한다.
- TTL만 믿고 방치하지 않는다. TTL은 누락된 무효화에 대한 보조 안전장치다.

## 2) Staff Summary Cache

Staff 목록 상단 summary count는 `StaffSummaryCache`를 사용한다.

- 위치: `app/Domains/Common/Cache/Support/StaffSummaryCache.php`
- 기본 store: `STAFF_SUMMARY_CACHE_STORE=redis`
- 기본 TTL: `STAFF_SUMMARY_CACHE_TTL=300`
- 키 형식: `staff:summary:{domain}:{suffix}`
- Redis 오류 시: 캐시를 건너뛰고 resolver DB 조회 결과를 반환한다.

적용 대상:

- 일반회원 summary
- 병의원 summary
- 입점신청 summary
- 이벤트 summary
- 동영상 summary
- 신고게시물 summary (`ContentReportSummaryCache`가 내부적으로 `StaffSummaryCache`를 사용)

## 3) Query 작성 규칙

Summary query는 아래 구조를 따른다.

```php
public function get(): array
{
    return StaffSummaryCache::remember(
        StaffSummaryCache::DOMAIN_HOSPITAL,
        fn (): array => $this->uncachedSummary(),
    );
}

private function uncachedSummary(): array
{
    // DB 집계 쿼리
}
```

규칙:

- `get()`은 캐시 진입점만 담당한다.
- 실제 DB 집계는 `uncachedSummary()`에 둔다.
- Controller/Action에서 summary count를 직접 만들지 않는다.
- 새로운 Staff summary API를 추가하면 `StaffSummaryCache`에 domain 상수를 먼저 추가한다.

## 4) 무효화 규칙

상태/카운트에 영향을 주는 write action 완료 후 해당 summary 캐시를 삭제한다.

예:

```php
$updated = DB::transaction(function () {
    // 원본 데이터 변경
});

StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);
```

현재 연결된 무효화 기준:

- 일반회원: 차단/정상 상태 변경, 신고 경고 처리로 `warning_count` 또는 차단 상태가 바뀌는 경우
- 병의원: 생성, 삭제, 검수상태 변경, 병의원상태 변경, 병원 계정 로그인으로 휴면 여부가 바뀌는 경우
- 입점신청: 검수상태 변경, 수정 payload에 `allow_status`가 포함된 경우
- 이벤트: 생성, 삭제, 검수상태 변경, 강제중지 변경, 기간 변경, 공개여부/검수/기간 관련 수정
- 동영상: 생성, 삭제, 강제중지 변경, 공개여부/강제중지 관련 수정, 동영상 신고 처리 상태 변경
- 신고게시물: 신고 생성, 신고 처리, 경고여부 변경

## 5) Redis Cache 적용 판단

적합:

- 관리자 summary count
- 카테고리/selector/options
- 광고 구좌 현황 달력
- FCM/APNs access token
- rate limit
- 72시간 신고오류 재신고 제한 보조 캐시

부적합:

- 충전금 원장
- 광고 구좌 최종 확정
- 승인/반려 최종 상태
- 운영 히스토리 원본

위 데이터는 DB를 원본으로 저장하고, Redis는 조회 또는 중복 방지 보조 수단으로만 사용한다.
