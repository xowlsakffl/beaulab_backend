# 콘텐츠 신고 / 신고게시물 관리

작성 기준: 2026-05-18

이 문서는 현재 코드 기준의 콘텐츠 신고 기능과 Staff 신고게시물 관리 구조를 정리한다.

## 1) 핵심 모델

| 모델 | 테이블 | 역할 |
|---|---|---|
| `ContentReport` | `content_reports` | 사용자 신고 건별 로그 |
| `ContentReportState` | `content_report_states` | 신고 대상별 현재 신고 상태와 집계 |

`ContentReport`는 신고 1건을 그대로 저장한다. `ContentReportState`는 `target_type + target_id`별로 1건만 유지하며, Staff 신고게시물 리스트와 상세 화면은 이 상태 테이블을 기준으로 조회한다.

## 2) 신고 대상

신고 대상 alias는 `ContentReportTargetRegistry`에서 관리한다.

| alias | 모델 | 화면/도메인 |
|---|---|---|
| `talk` | `Talk` | 토크 게시글 |
| `talk_comment` | `TalkComment` | 토크 댓글 |
| `hospital_review` | `HospitalReview` | 성형후기/시술후기 |
| `hospital_review_comment` | `HospitalReviewComment` | 후기 댓글 |
| `hospital_evaluation` | `HospitalEvaluation` | 병의원 평가 |

새 신고 대상이 추가되면 `ContentReportTargetRegistry`, User 신고 라우트, Staff 신고게시물 라우트, Policy 권한을 같이 갱신해야 한다.

## 3) 신고 사유

| 저장값 | 표시명 |
|---|---|
| `ABUSE` | 비방/욕설 |
| `SPAM` | 게시물/댓글 도배 |
| `ILLEGAL_AD` | 불법광고/홍보 |
| `PRIVACY_COPYRIGHT` | 개인정보/저작권 침해 |
| `OTHER` | 기타 |

`OTHER`일 때는 `reason_text`가 필요하다.

## 4) 신고 상태

| 저장값 | 표시명 | 의미 |
|---|---|---|
| `NONE` | 없음 | 신고 접수 전 기본값 |
| `REPORTED` | 신고접수 | 1건 이상 신고 접수 |
| `AUTO_BLOCKED` | 자동차단 | 1시간 내 신고 10건 이상으로 자동 미노출 |
| `ADMIN_HIDDEN` | 노출중지 | Staff가 신고게시물 관리에서 노출중지 처리 |
| `NORMAL_VISIBLE` | 정상노출 | Staff가 신고게시물 관리에서 정상노출 처리 |

`AUTO_BLOCKED`, `ADMIN_HIDDEN` 상태인 대상은 일반 게시물관리 화면에서 노출/미노출 변경이 잠긴다. 이 상태는 신고게시물 관리에서만 정상노출/노출중지로 처리한다.

## 5) 자동 상태 전이

사용자 신고 생성 흐름은 `ContentReportCreateForUserAction`이 담당한다.

1. 신고 로그를 `content_reports`에 생성한다.
2. 대상별 `content_report_states`를 생성하거나 잠금 조회한다.
3. 전체 신고 수(`report_count`)와 최근 1시간 신고 수(`recent_hour_report_count`)를 갱신한다.
4. 최근 1시간 신고 수가 10건 이상이면 `AUTO_BLOCKED`로 바꾸고 대상 콘텐츠 `status`를 `INACTIVE`로 바꾼다.
5. 기존 상태가 `NONE` 또는 `NORMAL_VISIBLE`이고 자동차단 기준 미만이면 `REPORTED`로 바꾼다.

관리자가 `NORMAL_VISIBLE`로 처리하면 `recent_hour_report_count`를 0으로 초기화한다. 따라서 정상노출 직후 신고 1건만 추가되어도 바로 자동차단되지 않고, 정상노출 이후 다시 10건이 쌓여야 자동차단된다.

`normal_visible_count`가 3 이상이면 `is_auto_action_locked()`가 true가 되어 이후 신고로 자동 신고접수/자동차단 상태가 되지 않는다.

## 6) Staff 처리

상태 처리는 `ContentReportStateStatusUpdateForStaffAction`이 담당한다.

| 요청 상태 | 결과 |
|---|---|
| `ADMIN_HIDDEN` | 신고 상태를 노출중지로 바꾸고 대상 콘텐츠 `status`를 `INACTIVE`로 변경 |
| `NORMAL_VISIBLE` | 신고 상태를 정상노출로 바꾸고 대상 콘텐츠 `status`를 `ACTIVE`로 변경 |

노출중지는 `process_reason`이 필수다. 처리 사유는 operation history의 `reason`에 저장된다.

## 7) 경고 / 무시

경고 처리는 `ContentReportWarningStatusUpdateForStaffAction`이 담당한다.

| 저장값 | 의미 |
|---|---|
| `NONE` | 경고/무시 미처리 |
| `WARNED` | 작성자에게 경고 반영 |
| `IGNORED` | 해당 신고 대상은 경고하지 않음 |

경고/무시는 `ADMIN_HIDDEN` 상태에서만 가능하다. `WARNED`로 바꾸면 대상 작성자의 `AccountUser.warning_count`가 1 증가한다. 누적 경고가 10건 이상이면 사용자는 `BLOCKED` 상태가 된다. `WARNED`에서 `IGNORED`로 변경하면 경고 수가 1 감소하고, 경고 수가 10 미만이 되면 차단 상태를 해제한다.

## 8) API 경로

User 신고 생성:

| Method | Path |
|---|---|
| `POST` | `/api/v1/user/talks/{talk}/reports` |
| `POST` | `/api/v1/user/talks/{talk}/comments/{comment}/reports` |
| `POST` | `/api/v1/user/hospital-reviews/{hospitalReview}/reports` |
| `POST` | `/api/v1/user/hospital-reviews/{hospitalReview}/comments/{comment}/reports` |
| `POST` | `/api/v1/user/hospital-evaluations/{hospitalEvaluation}/reports` |

Staff 신고게시물 관리:

| Method | Path |
|---|---|
| `GET` | `/api/v1/staff/reported-contents/talks` |
| `GET` | `/api/v1/staff/reported-contents/talk-comments` |
| `GET` | `/api/v1/staff/reported-contents/hospital-reviews/surgery` |
| `GET` | `/api/v1/staff/reported-contents/hospital-reviews/treatment` |
| `GET` | `/api/v1/staff/reported-contents/hospital-review-comments/surgery` |
| `GET` | `/api/v1/staff/reported-contents/hospital-review-comments/treatment` |
| `GET` | `/api/v1/staff/reported-contents/hospital-evaluations` |
| `GET` | `/api/v1/staff/reported-contents/detail/{targetType}/{targetId}` |
| `GET` | `/api/v1/staff/reported-contents/{targetType}/{targetId}/reports` |
| `PATCH` | `/api/v1/staff/reported-contents/status` |
| `PATCH` | `/api/v1/staff/reported-contents/warning-status` |

## 9) 목록 성능 기준

신고게시물 목록은 `content_report_states`를 기준으로 조회하고, 대상 콘텐츠는 `loadMorph()`로 필요한 관계만 로드한다. 신고내역 요약은 현재 페이지의 대상 묶음에 대해 한 번에 조회한다.

상단 요약 카드는 `ContentReportSummaryCache`로 5분 캐싱한다. 신고 생성, 신고 상태 처리, 경고/무시 처리 후에는 대상 기준 캐시를 무효화한다.

## 10) 페이지네이션

신고게시물 리스트와 신고내역 리스트는 `PaginatedResponse`를 사용한다.

- 리스트: `ReportedContentListForStaffAction`
- 신고내역: `ReportedContentReportsForStaffAction`
- 신고내역은 페이지가 비었을 때 `paginateWithFallback()`으로 1페이지를 반환한다.

공통 응답 구조는 `api-response.md`를 참고한다.
