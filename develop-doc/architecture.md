# Architecture (구조 / 흐름)

이 문서는 현재 코드 기준으로 Beaulab 백엔드 구조를 정리한다.  
핵심은 Actor(Staff/Hospital/Beauty/User) 진입점과 Domain 비즈니스 로직을 분리하는 것이다.

## 1) API 엔드포인트 구성

`routes/api.php`에서 v1 라우트를 Actor 단위로 분기한다.

- Staff API: `/api/v1/staff/*`
- Hospital API: `/api/v1/hospital/*`
- Beauty API: `/api/v1/beauty/*`
- User API: `/api/v1/user/*`

실제 상세 라우트는 `app/Modules/*/routes/api_*.php`에서 관리한다.

## 2) 디렉토리 구조 원칙

- `app/Modules/*`
  - HTTP 진입점(Controller, Request, Route)
  - 인증/인가, 입력 검증, 응답 포맷 처리
- `app/Domains/*`
  - 비즈니스 로직(Action, Query, Policy, Model, DTO)
  - 도메인 규칙과 상태 전이 관리
- `app/Common/*`
  - 공통 응답, 예외, 권한 상수, 공통 미들웨어

## 3) 요청 처리 흐름

1. Module Controller에서 요청 검증
2. Policy/Gate/Permission으로 인가 확인
3. Domain Action/Query로 비즈니스 처리
4. `ApiResponse` 포맷으로 응답 반환

## 4) 인증/인가 흐름

- Staff 보호 라우트
  - `auth:sanctum`
  - `abilities:actor:staff`
  - `permission:common.access`
- Hospital 보호 라우트
  - `auth:sanctum`
  - `abilities:actor:hospital`
- Beauty 보호 라우트
  - `auth:sanctum`
  - `abilities:actor:beauty`

## 5) 현재 주요 도메인

- 계정: `AccountStaff`, `AccountHospital`, `AccountBeauty`, `AccountUser`, `AccountUserAccessLog`, `AccountUserBlock`
- 파트너: `Hospital`, `Beauty`, `HospitalDoctor`, `BeautyExpert`, `HospitalFeature`
- 병원 이벤트/고객 DB: `HospitalEvent`, `HospitalEventDB`, `HospitalEventRealModelDB`, `HospitalEventOption`, `HospitalEventDoctorAssignment`
- 콘텐츠: `Talk`, `TalkComment`, `TalkCommentMention`, `TalkPoll`, `TalkPollOption`, `TalkPollVote`, `TalkSave`, `HospitalReview`, `HospitalReviewComment`, `HospitalReviewCommentMention`, `HospitalEvaluation`, `HospitalVideo`, `Notice`, `Faq`
- 커뮤니케이션: `Chat`, `ChatMessage`, `ChatParticipant`, `NotificationInbox`, `NotificationDelivery`, `NotificationDevice`, `NotificationPreference`
- 공통 운영: `Media`, `Category`, `CategoryUsage`, `Hashtag`, `AdminNote`, `ContentReport`, `ContentReportItem`, `ContentReportState`, `OperationHistory`, `OperationHistoryChange`

파트너 계정 관계:

- `AccountHospital`과 `Hospital`은 1:1이다. 병원 계정은 `account_hospitals.hospital_id` unique 제약으로 병원당 하나만 존재한다.
- `AccountBeauty`와 `Beauty`는 1:1이다. 뷰티 계정은 `account_beauties.beauty_id` unique 제약으로 뷰티 업체당 하나만 존재한다.
- Staff 상세 응답은 복수 계정 배열을 쓰지 않고 `account_hospital`, `account_beauty` 단일 객체를 사용한다.
- Hospital/Beauty Actor API는 로그인 계정의 `hospital_id`, `beauty_id`를 소유권 기준으로 사용한다.

## 6) 공지사항(Notice) / FAQ 구조

현재 Notice / FAQ는 Staff API 기준으로 구현되어 있다.

- 라우트: `app/Modules/Staff/routes/api_staff.php`
- 컨트롤러:
  - `app/Modules/Staff/Http/Controllers/Notice/NoticeForStaffController.php`
  - `app/Modules/Staff/Http/Controllers/Faq/FaqForStaffController.php`
- 도메인:
  - `app/Domains/Notice/*`
  - `app/Domains/Faq/*`
  - FAQ 카테고리는 공통 `Category` 도메인의 `FAQ` 분류 사용

기능 범위:

1. 공지 CRUD
2. 채널/상태/상단고정/게시기간
3. 첨부파일 업로드
4. 에디터 이미지 업로드/정리
5. 관리자 메인 팝업(`is_important`)
6. FAQ CRUD
7. FAQ 에디터 이미지 업로드/정리

## 7) 병의원 게시물 운영 구조

병의원 게시물 계열은 Actor 진입점과 Domain 로직을 분리한다.

- Staff 라우트
  - `GET /api/v1/staff/hospital-reviews/surgery`
  - `GET /api/v1/staff/hospital-reviews/treatment`
  - `GET /api/v1/staff/hospital-review-comments`
  - `GET /api/v1/staff/hospital-evaluations`
  - `GET /api/v1/staff/reported-contents/talks`
  - `GET /api/v1/staff/reported-contents/talk-comments`
  - `GET /api/v1/staff/reported-contents/hospital-reviews/surgery`
  - `GET /api/v1/staff/reported-contents/hospital-reviews/treatment`
  - `GET /api/v1/staff/reported-contents/hospital-review-comments/surgery`
  - `GET /api/v1/staff/reported-contents/hospital-review-comments/treatment`
  - `GET /api/v1/staff/reported-contents/hospital-evaluations`
  - `GET /api/v1/staff/reported-contents/detail/{targetType}/{targetId}`
  - `GET /api/v1/staff/reported-contents/{targetType}/{targetId}/reports`
  - `PATCH /api/v1/staff/hospital-reviews/status`
  - `PATCH /api/v1/staff/hospital-review-comments/status`
  - `PATCH /api/v1/staff/hospital-evaluations/status`
  - `PATCH /api/v1/staff/hospital-evaluations/{hospitalEvaluation}/receipt/verify`
  - `PATCH /api/v1/staff/hospital-evaluations/{hospitalEvaluation}/receipt/reject`
  - `PATCH /api/v1/staff/reported-contents/status`
  - `PATCH /api/v1/staff/reported-contents/warning-status`
- User 라우트
  - `POST /api/v1/user/hospital-reviews`
  - `DELETE /api/v1/user/hospital-reviews/{hospitalReview}`
  - `POST /api/v1/user/hospital-reviews/{hospitalReview}/comments`
  - `DELETE /api/v1/user/hospital-reviews/{hospitalReview}/comments/{comment}`
  - `POST /api/v1/user/talks/{talk}/reports`
  - `POST /api/v1/user/talks/{talk}/comments/{comment}/reports`
  - `POST /api/v1/user/hospital-reviews/{hospitalReview}/reports`
  - `POST /api/v1/user/hospital-reviews/{hospitalReview}/comments/{comment}/reports`
  - `POST /api/v1/user/hospital-evaluations/{hospitalEvaluation}/reports`

도메인 책임:

- `HospitalReview`: 성형후기/시술후기 게시글, 병원/의료진/카테고리/전후 이미지/평점/비용/베스트/통계
- `HospitalReviewComment`: 후기 댓글/대댓글, 멘션, 노출상태, 게시상태, 처리 이력
- `HospitalEvaluation`: 병의원 평가, 병원/의료진/카테고리, 평가 항목, 영수증 이미지/인증/부적합 사유, 처리 이력
- `Talk`: 토크 게시글, 카테고리, 이미지, 투표, 통계, 처리 이력
- `TalkComment`: 토크 댓글/대댓글, 멘션, 노출상태, 게시상태, 처리 이력
- `ContentReport`: 사용자 신고 건별 로그
- `ContentReportState`: 신고 대상별 현재 신고 상태, 신고 수, 경고/무시 상태

DTO 응답 원칙:

- `author`, `hospital`, `doctor`, `category`, `parent`, `receipt`처럼 연관 객체는 개별 id/name 필드로 흩뿌리지 않고 객체로 내려준다.
- 카테고리가 여러 개인 도메인은 `categories` 배열로 내려준다.
- 목록 DTO는 eager loaded relation을 기준으로 만들고, 상세 DTO처럼 별도 조합 로직이 긴 경우 private resolver로 분리한다.

신고 상태 원칙:

- 일반 콘텐츠의 실제 노출 여부는 각 도메인 모델의 `status`로 관리한다.
- 신고 접수/자동차단/노출중지/정상노출 상태는 `ContentReportState.report_status`로 분리 관리한다.
- `AUTO_BLOCKED`, `ADMIN_HIDDEN` 상태인 콘텐츠는 일반 게시물관리에서 노출/미노출 변경이 잠긴다.
- 신고 상태 변경, 경고/무시 처리는 operation history에 기록한다.

운영 히스토리 원칙:

- 관리자 화면에 표시할 처리 이력은 `operation_histories`에 부모 이력으로 저장한다.
- 변경 필드별 전/후 값은 `operation_history_changes`에 저장한다.
- 단건 변경도 change 1건으로 저장하고, 다중 변경은 부모 이력 1건에 여러 change를 붙인다.
- 도메인 Action은 `OperationHistoryChangeSetBuilder`로 변경 payload를 만들고, 저장은 `OperationHistoryCreateAction`에 맡긴다.
- 상세 구조는 `./operation-history.md`를 따른다.

## 8) API 응답 / 페이지네이션 원칙

`LengthAwarePaginator` 기반 목록은 `App\Common\Support\PaginatedResponse`를 사용한다.

- `fromPaginator($paginator, $mapper)`: `items`와 `meta.current_page/per_page/total/last_page`를 만든다.
- `fromPaginator($paginator, $mapper, $extraMeta)`: 신고게시물 요약처럼 추가 meta가 필요할 때 사용한다.
- `paginateWithFallback()`: 상세 댓글/히스토리/신고내역처럼 빈 페이지가 생기기 쉬운 목록에서 1페이지 fallback을 제공한다.

예외:

- `ChatMessageListForUserQuery`는 cursor pagination을 사용하므로 `current_page/total` 기반 `PaginatedResponse`를 사용하지 않는다.

## 9) 비동기 구조 연결

비동기 처리는 API 계층과 분리되어 동작한다.

1. API/Action에서 Job dispatch
2. Redis Queue 적재
3. Horizon 워커 처리

운영 상세는 아래 문서 참고:

- Queue: `./queue.md`
- Scheduler: `./scheduler.md`

## 10) 현재 API 범위 요약

- Staff
  - 인증, 프로필/비밀번호 수정, 관리자 메모, 대시보드
  - 병원/뷰티/일반회원/의료진/뷰티전문가 관리
  - 병원 특징, 카테고리, 해시태그 관리
  - 병원 이벤트, 이벤트 DB, 리얼모델 DB 관리
  - 동영상 목록/상세/생성/수정/삭제/원본 다운로드
  - 토크/토크댓글, 병의원 후기/후기댓글, 병의원 평가/영수증 인증 관리
  - 신고게시물 관리, 신고 상태 처리, 경고/무시 처리, 채팅 메시지 신고 조회
  - 공지사항/FAQ CRUD와 에디터 이미지 업로드/정리
- Hospital
  - 인증, 프로필/비밀번호 수정, 관리자 메모
  - 병원 동영상 요청 생성과 파트너 취소
  - 현재 Hospital Actor API에는 동영상 목록/상세/수정 라우트가 없다.
- Beauty
  - 인증, 프로필/비밀번호 수정, 관리자 메모
  - 현재 Beauty Actor API에는 뷰티 파트너 업무 라우트가 없다.
- User
  - 인증, 프로필/비밀번호 수정
  - 채팅방/메시지/읽음/알림 설정, 채팅 메시지 신고
  - 토크 작성/삭제/댓글/투표/신고
  - 병의원 후기 작성/삭제/댓글/신고, 병의원 평가 신고
  - 병원 이벤트 DB/리얼모델 DB 신청
  - 사용자 차단/해제, 알림함/디바이스/알림 설정

## 11) 체크리스트

- [ ] 새 API가 Actor 경계에 맞게 배치됐는가?
- [ ] 컨트롤러가 얇게 유지되고 비즈니스 로직이 Domain으로 내려갔는가?
- [ ] 정책/권한/시더가 함께 갱신됐는가?
- [ ] 목록 응답이 `PaginatedResponse` 또는 명시적인 cursor pagination 규칙을 따르는가?
- [ ] 신고 대상 추가 시 `ContentReportTargetRegistry`, User 신고 라우트, Staff 신고게시물 라우트가 같이 갱신됐는가?
- [ ] 비동기 작업이 lane 정책(`critical`, `mail`, `sms`, `chat`, `default` 등)에 맞게 라우팅됐는가?

작성 기준: 2026-06-22
