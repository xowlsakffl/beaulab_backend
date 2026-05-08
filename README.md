# 뷰랩 - 성형·뷰티 중개 플랫폼 백엔드

뷰랩은 뷰랩 성형·뷰티 중개 앱 플랫폼의 API 서버입니다. 일반 사용자, 병원 파트너, 뷰티 파트너, 내부 운영자가 각각 다른 역할과 권한으로 서비스를 사용하므로 API 진입점과 인증 정책을 Actor 기준으로 분리했습니다.

뷰랩 플랫폼은 병원과 뷰티 업체 관리, 의료진과 전문가 프로필 관리, 게시 영상 관리, 이벤트 관리 등 전반적인 관리자 기능을 하나의 백엔드에서 관리합니다. 앱 클라이언트와 관리자/파트너 클라이언트는 REST API와 라라벨 Reverb channel을 통해 서비스 데이터를 조회하고 실시간 이벤트를 동기화합니다.

이 저장소는 뷰랩 앱 플랫폼의 백엔드 계층을 담당합니다. API 라우팅, 인증/인가, 도메인 비즈니스 로직, DB 마이그레이션, 권한 시더, 큐/스케줄러, 실시간 브로드캐스트, 푸시 알림, 내부 운영 도구 접근 제어를 포함합니다.

## 서비스 범위

이 README의 서비스 범위는 현재 구현된 기능과 성형·미용 중개 앱 플랫폼으로 확장할 예정인 기능을 함께 포함합니다. 뷰랩은 성형외과/피부과/뷰티 업체 탐색, 후기, 이벤트, 상담, 예약, 견적, 커뮤니티, 채팅, 알림, 운영 검수까지 연결하는 플랫폼을 목표로 합니다.

- 일반 사용자 앱
  - 사용자 로그인, 프로필 관리, 관심 지역/관심 시술 설정
  - 병원, 뷰티 업체, 의료진, 전문가 검색/필터/상세 조회
  - 성형/시술 카테고리, 부위별 탐색, 지역별 탐색, 가격대 필터
  - 병원/뷰티 이벤트, 특가, 프로모션, 쿠폰 조회
  - 시술/수술 가격 정보, 이벤트 가격, 정상가/할인가 관리
  - 전후 사진, 시술 사진, 영상 콘텐츠 조회
  - 리얼 후기, 영수증 인증 후기, 전후 사진 후기, 부작용 후기
  - 후기 좋아요, 저장, 댓글, 신고, 블라인드 요청
  - 병원/시술 즐겨찾기, 최근 본 항목, 토크 저장
  - 1:1 채팅 상담, 병원 상담 문의, 견적 요청, 상담 상태 확인
  - 예약 신청, 예약 변경/취소, 방문 완료, 노쇼/취소 정책 연동
  - 맞춤 추천, 인기 검색어, 홈 배너, 기획전, 랭킹
  - 사용자 커뮤니티, 질문/답변, 수술 고민, 회복 과정 공유
  - 사용자 차단, 게시글/댓글/채팅 신고, 알림 수신 설정
  - 인앱 알림, Push 알림, 이벤트 알림, 상담/예약 알림
- 병원 파트너
  - 병원 계정 로그인, 프로필 관리, 운영 메모
  - 병원 기본 정보, 위치, 진료 시간, 연락처, 소개 관리
  - 의료진 프로필, 전문 분야, 경력, 노출 상태 관리
  - 성형/시술 상품, 가격, 이벤트, 쿠폰, 프로모션 관리
  - 전후 사진, 영상, 썸네일, 첨부파일 관리
  - 상담 문의 수신, 1:1 채팅 응대, 견적 답변, 예약 관리
  - 후기 답글, 신고 대응, 운영자 검수 요청
  - 영상 게시 요청 생성/취소
  - 파트너 계정/직원 권한 관리
- 뷰티 파트너
  - 뷰티 계정 로그인, 프로필 관리, 운영 메모
  - 뷰티 업체 기본 정보, 위치, 영업 시간, 연락처, 소개 관리
  - 전문가 프로필, 경력, 노출 상태 관리
  - 시술/관리 상품, 가격, 이벤트, 쿠폰, 프로모션 관리
  - 상담 문의 수신, 1:1 채팅 응대, 예약 관리
  - 후기 답글, 신고 대응, 운영자 검수 요청
  - 파트너 계정/직원 권한 관리
- 내부 운영자
  - 병원/뷰티 업체, 회원, 의사, 전문가, 영상 요청, 토크, 공지사항, FAQ, 카테고리, 해시태그 관리
  - 병원/뷰티 검수, 의료진/전문가 검수, 영상 게시 검수
  - 이벤트/프로모션/쿠폰 검수와 노출 관리
  - 후기/전후 사진/부작용 후기 검수와 블라인드 처리
  - 게시글/댓글/채팅/후기 신고 접수와 처리
  - 회원 제재, 파트너 제재, 콘텐츠 노출 제어
  - 홈 배너, 랭킹, 추천 영역, 기획전, 팝업 관리
  - 검색 키워드, 인기 검색어, 카테고리, 지역 관리
  - 상담/예약 현황, 파트너 응대 상태, 운영 메모 관리
  - 통계 대시보드, 감사 로그, 운영 로그, 내부 도구 관리
- 공통 운영 인프라
  - Actor별 인증/권한, 공통 응답/예외 처리, 감사 로그, Redis Queue, Horizon, Reverb, Push Notification, Scheduler
  - DDD 기반 도메인 계층, Actor별 API 모듈, 공통 Media/Notification/AdminNote 모듈
  - 실시간 채팅/알림, 비동기 Push 발송, 스케줄러, 내부 운영 도구 허브
  - 콘텐츠 검수, 신고 처리, 블라인드, 제재, 알림, 검색, 추천 확장 기반

## Actor 구조

뷰랩 API는 `routes/api.php`에서 v1 라우트를 Actor 단위로 나눕니다.

| Actor | Prefix | 대상 | 주요 역할                                   |
| --- | --- | --- |-----------------------------------------|
| Staff | `/api/v1/staff` | 뷰랩 내부 운영자 | 백오피스 관리, 검수, 콘텐츠/파트너/회원 관리, 전반적인 플랫폼 관리 |
| Hospital | `/api/v1/hospital` | 병원 파트너 | 병원 관리                                   |
| Beauty | `/api/v1/beauty` | 뷰티 파트너 | 뷰티 업체 관리                                |
| User | `/api/v1/user` | 일반 앱 사용자 | 사용자 전반적 기능                              |

보호 라우트는 Laravel Sanctum 토큰과 Actor ability를 함께 확인합니다.

| Actor | 인증/인가 기준 |
| --- | --- |
| Staff | `auth:sanctum`, `abilities:actor:staff`, `permission:common.access` |
| Hospital | `auth:sanctum`, `abilities:actor:hospital` |
| Beauty | `auth:sanctum`, `abilities:actor:beauty` |
| User | `auth:sanctum`, `abilities:actor:user` |

브라우저 기반 내부 운영 도구는 API 토큰이 아니라 `tool_staff` 세션 가드를 사용합니다. Horizon, Telescope, Swagger 허브는 IP allowlist와 `viewTool` Gate를 함께 통과해야 접근할 수 있습니다.

## DDD 기반 설계

뷰랩 백엔드는 Laravel 기반 모듈러 모놀리스 구조 안에서 DDD를 지향합니다. HTTP 진입점은 Actor별 `Module`로 분리하고, 실제 업무 규칙과 상태 변경은 `Domain` 계층에 둡니다. 즉, `Staff`, `Hospital`, `Beauty`, `User`는 API 사용 주체의 경계이고, `Hospital`, `Talk`, `Chat`, `Notification` 같은 도메인은 업무 책임의 경계입니다.

### 계층 구조

| 계층 | 위치 | 책임 |
| --- | --- | --- |
| Interface / Module | `app/Modules/*` | Actor별 Route, Controller, Request 검증, 인증 사용자 추출, 응답 연결 |
| Application Use Case | `app/Domains/*/Actions` | 하나의 유스케이스 실행, 트랜잭션 경계, 여러 Query/Model 호출 조합 |
| Domain Query | `app/Domains/*/Queries` | 도메인 조회/저장 조건 캡슐화, 목록 필터, 상태 변경에 필요한 데이터 접근 |
| Domain Model | `app/Domains/*/Models` | Eloquent 모델, 상태 상수, 관계, 도메인 데이터 기준 |
| Policy / Authorization | `app/Domains/*/Policies`, `app/Common/Authorization` | 도메인별 접근 제어, Role/Permission 상수 관리 |
| DTO | `app/Domains/*/Dto` | API 응답에 사용할 도메인 데이터 변환 |
| Shared Kernel | `app/Common`, `app/Domains/Common` | 공통 응답, 예외, 미들웨어, Media, Category, Notification, AdminNote |

### Bounded Context

| Context | 포함 도메인 | 설명 |
| --- | --- | --- |
| Account | `AccountStaff`, `AccountHospital`, `AccountBeauty`, `AccountUser` | Actor별 로그인 계정과 상태 관리 |
| Partner | `Hospital`, `Beauty`, `HospitalDoctor`, `BeautyExpert`, `HospitalBusinessRegistration`, `BeautyBusinessRegistration` | 병원/뷰티 파트너 정보, 검수, 노출 관리 |
| Video | `HospitalVideo` | 병원 영상 게시 요청, 검토, 게시 상태 관리 |
| Community | `Talk`, `TalkComment`, `TalkSave` | 사용자 커뮤니티 게시글, 댓글, 저장 기능 |
| Communication | `Chat`, `ChatMessage`, `NotificationInbox`, `NotificationDelivery`, `NotificationDevice`, `NotificationPreference` | 사용자 간 채팅, 실시간 이벤트, 인앱/푸시 알림 |
| Operation Content | `Notice`, `Faq`, `Category`, `Hashtag`, `Media`, `AdminNote` | 운영 콘텐츠, 공통 분류, 파일 메타데이터, 운영자 메모 |

### 요청 처리 흐름

```text
Client
  -> routes/api.php
  -> app/Modules/{Actor}/routes/api_*.php
  -> FormRequest validation
  -> Controller
  -> Policy / Permission
  -> Domain Action
  -> Domain Query / Model
  -> DTO
  -> ApiResponse
```

Controller는 요청을 해석하고 응답을 연결하는 역할만 담당합니다. 병원 생성, 채팅 메시지 저장, 알림 생성, 영상 요청 취소처럼 상태를 바꾸는 규칙은 각 도메인의 Action/Query/Model에 위치합니다.

### 설계 원칙

- Actor 경계와 Domain 경계를 분리합니다.
- Controller에 비즈니스 규칙을 두지 않습니다.
- 하나의 유스케이스는 Action 단위로 표현합니다.
- 조회/저장 조건과 상태 변경에 필요한 데이터 접근은 Query로 분리합니다.
- 도메인 상태값은 Model 상수로 관리합니다.
- 도메인별 접근 제어는 Policy와 Permission으로 명시합니다.
- 여러 도메인에서 재사용되는 기능은 `app/Domains/Common`으로 이동합니다.
- 기술 종류별 폴더보다 업무 소유권 기준으로 코드를 배치합니다.
- 큐 Job도 `Jobs` 공용 폴더에 몰아넣지 않고 소유 도메인 하위에 둡니다.

이 구조는 기능이 늘어날 때 Actor별 API 파일만 커지는 것을 막고, 병원/뷰티/채팅/알림/콘텐츠처럼 업무 단위로 변경 영향을 좁히기 위한 설계입니다.

## 주요 기능

### Staff API

- Staff 로그인/로그아웃
- 내 프로필 조회/수정
- 비밀번호 변경
- 대시보드 API
- 관리자 메모(Admin Note) 조회/작성/수정
- 병원 관리
  - 병원 목록/상세 조회
  - 병원 생성/수정/삭제
  - 병원명 중복 확인
  - 사업자번호 중복 확인
  - 병원 특징 옵션 조회
- 뷰티 업체 관리
  - 뷰티 목록/상세 조회
  - 뷰티 생성/수정/삭제
- 일반 회원 관리
  - 회원 목록/상세 조회
  - 회원 수정/삭제
- 병원 의사 관리
  - 의사 목록/상세 조회
  - 의사 생성/수정/삭제
  - 의사 등록용 병원 옵션 조회
- 뷰티 전문가 관리
  - 전문가 목록/상세 조회
  - 전문가 생성/수정/삭제
- 병원 영상 요청 관리
  - 영상 목록/상세 조회
  - 영상 생성/수정/삭제
  - 영상 파일 다운로드
  - 영상 등록용 병원/의사 옵션 조회
- 카테고리 관리
  - 카테고리 목록/상세 조회
  - 카테고리 selector 조회
  - 카테고리 생성/수정/삭제
- 해시태그 관리
  - 해시태그 목록/상세 조회
  - 해시태그 생성/수정/삭제
- 토크 관리
  - 토크 목록/상세 조회
  - 토크 생성/수정/삭제
  - 토크 노출 상태 일괄 변경
- 토크 댓글 관리
  - 댓글 목록/상세 조회
  - 댓글 생성/수정/삭제
- 공지사항 관리
  - 공지 목록/상세 조회
  - 공지 생성/수정/삭제
  - 공지 에디터 이미지 업로드/정리
  - 채널, 상태, 상단 고정, 게시기간, 관리자 메인 팝업 관리
- FAQ 관리
  - FAQ 목록/상세 조회
  - FAQ 생성/수정/삭제
  - FAQ 에디터 이미지 업로드/정리
  - 공통 Category의 FAQ 분류 사용

### Hospital API

- 병원 파트너 로그인/로그아웃
- 내 프로필 조회/수정
- 비밀번호 변경
- 관리자 메모 조회/작성/수정
- 영상 게시 요청 생성
- 영상 게시 요청 취소

### Beauty API

- 뷰티 파트너 로그인/로그아웃
- 내 프로필 조회/수정
- 비밀번호 변경
- 관리자 메모 조회/작성/수정

### User API

- 사용자 로그인/로그아웃
- 내 프로필 조회/수정
- 비밀번호 변경
- 1:1 채팅
  - 채팅방 목록 조회
  - 첫 메시지 기반 채팅방 생성
  - 메시지 목록 조회
  - 메시지 전송
  - 읽음 처리
  - 채팅방별 알림 on/off
  - 사용자별 채팅방 삭제
- 사용자 차단
  - 차단 목록 조회
  - 사용자 차단
  - 차단 해제
- 토크 저장
  - 토크 저장
  - 토크 저장 취소
- 알림
  - 알림 목록 조회
  - 미읽음 수 조회
  - 단건 읽음 처리
  - 전체 읽음 처리
  - Push device 등록/해제
  - 이벤트별 알림 수신 설정 조회/변경

## 주요 도메인

| 도메인 | 설명 |
| --- | --- |
| `AccountStaff` | 뷰랩 내부 운영자 계정 |
| `AccountHospital` | 병원 파트너 계정 |
| `AccountBeauty` | 뷰티 파트너 계정 |
| `AccountUser` | 일반 앱 사용자 계정 |
| `Hospital` | 병원 기본 정보, 노출, 검수, 운영 상태 |
| `Beauty` | 뷰티 업체 기본 정보, 노출, 검수, 운영 상태 |
| `HospitalDoctor` | 병원 소속 의사 프로필과 노출 정보 |
| `BeautyExpert` | 뷰티 소속 전문가 프로필과 노출 정보 |
| `HospitalBusinessRegistration` | 병원 사업자등록 정보와 등록증 파일 |
| `BeautyBusinessRegistration` | 뷰티 사업자등록 정보와 등록증 파일 |
| `HospitalVideo` | 병원 영상 게시 요청, 검수, 게시 정보 |
| `Talk` | 사용자 커뮤니티 게시글 |
| `TalkComment` | 토크 댓글, 대댓글, 멘션 |
| `TalkSave` | 사용자별 토크 저장 |
| `Chat` | 앱 사용자 간 1:1 채팅방 |
| `ChatMessage` | 채팅 메시지와 첨부 |
| `NotificationInbox` | 공통 인앱 알림함 |
| `NotificationDelivery` | 알림 채널별 발송 이력 |
| `NotificationDevice` | Push 토큰과 디바이스 정보 |
| `NotificationPreference` | 이벤트별 알림 수신 설정 |
| `Notice` | 공지사항, 게시기간, 상단 고정, 중요 팝업 |
| `Faq` | FAQ, 채널, 카테고리, 조회수 |
| `Category` | 공통 카테고리 |
| `Hashtag` | 공통 해시태그 |
| `Media` | 이미지/영상/첨부파일 메타데이터 |
| `AdminNote` | 운영자 메모 |

## 상태 흐름

- 계정 상태
  - Staff/User는 기본 활성 상태입니다.
  - Hospital/Beauty 파트너 계정은 기본 정지 상태이며 운영 승인 흐름을 거쳐 활성화됩니다.
  - 계정 공통 상태는 `ACTIVE`, `SUSPENDED`, `BLOCKED`를 사용합니다.
- 병원/뷰티 검수
  - `PENDING` -> `APPROVED` 또는 `REJECTED`
  - 운영 상태는 `ACTIVE`, `SUSPENDED`, `WITHDRAWN`을 사용합니다.
- 의사/전문가 검수
  - `PENDING` -> `APPROVED` 또는 `REJECTED`
  - 운영 상태는 `ACTIVE`, `SUSPENDED`, `INACTIVE`를 사용합니다.
- 영상 요청 검토
  - `allow_status`는 `SUBMITTED`에서 시작하고, 운영 검토 흐름에 따라 `IN_REVIEW`, `APPROVED`, `REJECTED`, `PARTNER_CANCELED`, `EXCLUDED` 등으로 관리합니다.
  - 파트너는 신청 단계의 요청만 취소할 수 있습니다.
- 공지사항/FAQ
  - `ACTIVE`, `INACTIVE` 상태를 사용합니다.
  - 공지사항은 게시기간, 상단 고정, 중요 팝업 여부를 별도 필드로 관리합니다.

## 채팅 설계

현재 채팅 범위는 앱 사용자(`account_users`) 간 1:1 채팅입니다.

- 채팅방은 첫 메시지를 보낼 때 생성합니다.
- 같은 두 사용자는 `match_key` 기준으로 하나의 채팅방만 가집니다.
- 메시지는 실시간 전송보다 DB 저장을 먼저 수행합니다.
- 텍스트, 이미지, 파일 메시지를 지원합니다.
- 이미지/파일 첨부는 공통 `Media` 테이블을 재사용합니다.
- `client_message_id`를 사용해 앱 재전송 시 메시지 중복 저장을 방지합니다.
- 채팅 삭제는 전체 삭제가 아니라 사용자별 `deleted_until_message_id` 기준 숨김 처리입니다.
- 사용자 차단은 방향성 있는 관계로 저장합니다.
- 어느 한쪽이라도 차단 관계가 있으면 메시지는 저장하지 않고 알림도 생성하지 않습니다.
- Reverb private channel로 메시지 생성과 읽음 상태 변경을 전달합니다.

실시간 채널:

| 채널 | 설명 |
| --- | --- |
| `private-chat.{chatId}` | 채팅 참여자만 구독 가능한 채팅방 채널 |
| `.chat.message.created` | 새 메시지 이벤트 |
| `.chat.read.updated` | 읽음 상태 변경 이벤트 |

## 알림 설계

알림은 Common 도메인 아래에 둔 공통 기능입니다. 채팅, 댓글, 이후 추가될 도메인 이벤트가 같은 알림 구조를 사용할 수 있습니다.

- 인앱 알림과 모바일 Push 알림을 지원합니다.
- 이벤트별 `in_app`, `push`, `email` 수신 설정을 관리합니다.
- 반복 이벤트는 원시 이벤트를 그대로 쌓지 않고 집계형 알림으로 관리합니다.
- `aggregation_key`로 같은 알림 묶음을 판별합니다.
- `open_aggregation_key`로 읽지 않은 집계 버킷을 수신자 기준 1개만 유지합니다.
- 알림 읽음 처리 시 `open_aggregation_key`를 비워 다음 이벤트가 새 묶음으로 생성될 수 있게 합니다.
- Push 발송 대상은 알림 설정과 활성 디바이스 토큰을 함께 확인합니다.
- 발송 이력은 `notification_deliveries`에 저장합니다.
- Push 발송은 `notifications` 큐에서 FCM/APNs provider로 처리합니다.

실시간 채널:

| 채널 | 설명 |
| --- | --- |
| `private-user.{userId}` | 본인만 구독 가능한 사용자 알림 채널 |
| `.notification.inbox.updated` | 알림함 업데이트 이벤트 |

## 인증과 권한

- API 인증은 Laravel Sanctum 기반입니다.
- Actor별 guard/provider를 분리합니다.
- 로그인 성공 시 Actor별 ability를 가진 토큰을 발급합니다.
- Staff, Hospital, Beauty는 Spatie Permission으로 Role/Permission을 관리합니다.
- 권한 문자열은 `AccessPermissions`, 역할 문자열은 `AccessRoles`를 단일 소스로 사용합니다.
- 권한 변경 후 `AuthorizationSeeder`로 DB 권한을 동기화합니다.

주요 Role:

| Guard | Role |
| --- | --- |
| Staff | `beaulab.super_admin`, `beaulab.admin`, `beaulab.staff`, `beaulab.dev` |
| Hospital | `hospital.owner`, `hospital.manager`, `hospital.staff` |
| Beauty | `beauty.owner`, `beauty.manager`, `beauty.staff` |
| User | 현재 role 기반 매핑 없음 |

## 공통 응답과 예외 처리

모든 API 응답은 `ApiResponse` 형식을 따릅니다.

성공 응답:

```json
{
  "success": true,
  "data": {},
  "meta": null,
  "traceId": "request-trace-id"
}
```

에러 응답:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "요청 값이 올바르지 않습니다."
  },
  "traceId": "request-trace-id"
}
```

예외 처리는 `bootstrap/app.php`에서 단일 지점으로 관리합니다.

| 예외 | HTTP | ErrorCode |
| --- | ---: | --- |
| Validation 실패 | 422 | `INVALID_REQUEST` |
| 인증 실패 | 401 | `UNAUTHORIZED` |
| 권한 실패 | 403 | `FORBIDDEN` |
| 리소스 없음 | 404 | `NOT_FOUND` |
| 허용되지 않은 메서드 | 405 | `METHOD_NOT_ALLOWED` |
| 토큰 오류 | 419 | `TOKEN_ERROR` |
| 요청 과다 | 429 | `RATE_LIMITED` |
| DB 오류 | 500 | `DB_ERROR` |
| 기타 서버 오류 | 500 | `INTERNAL_ERROR` |

`RequestId` 미들웨어는 `X-Request-Id`를 읽거나 UUID를 생성해 로그 컨텍스트, 응답 헤더, API 응답의 `traceId`에 반영합니다.

## 비동기 처리와 운영

Queue 표준 런타임은 Redis + Horizon입니다. Job은 기술 종류가 아니라 도메인 소유권 기준으로 배치합니다.

큐 레인:

| Queue | 용도 |
| --- | --- |
| `critical` | 사용자 영향도가 큰 고우선 작업 |
| `mail` | 메일 발송 |
| `sms` | 문자 발송 |
| `chat` | 채팅 비동기 처리 |
| `notifications` | Push/알림 비동기 처리 |
| `default` | 일반 비동기 작업 |
| `maintenance` | 정리, 백필, 유지보수 작업 |

운영 스케줄:

| 작업 | 주기 |
| --- | --- |
| `schedule-monitor:sync` | 매일 02:50 |
| `notice:cleanup-temp-editor-images --hours=24` | 매시간 |
| `horizon:snapshot` | 5분마다 |
| `queue:prune-batches --hours=72 --unfinished=72 --cancelled=168` | 매일 03:10 |
| `queue:prune-failed --hours=168` | 매일 03:20 |

운영 서버에서는 OS crontab이 매분 `php artisan schedule:run`을 실행하고, Spatie Schedule Monitor가 스케줄 실행 상태를 기록합니다.

## 내부 운영 도구

내부 운영 도구는 API Staff 인증과 분리된 `tool_staff` 세션 인증을 사용합니다.

- 로그인 화면: `/staff/tools/login`
- 내부 도구 허브: `/staff/tools`
- Horizon: `/horizon`
- Telescope: `/telescope`
- Swagger: `INTERNAL_TOOL_SWAGGER_URL` 설정값 기준

접근 조건:

- 허용 IP 목록 통과
- `tool_staff` 세션 로그인
- `viewTool` Gate 통과
- `ACTIVE` Staff 계정
- `beaulab.super_admin` 또는 `beaulab.dev` 역할
- `INTERNAL_TOOL_ALLOWED_EMAILS`가 설정된 경우 이메일 allowlist 통과

## 기술 스택

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Laravel Sanctum](https://img.shields.io/badge/Sanctum-API%20Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Laravel Reverb](https://img.shields.io/badge/Reverb-Realtime-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Queue%20%2B%20Cache-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-Local-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![Horizon](https://img.shields.io/badge/Laravel%20Horizon-Queue-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)

- PHP 8.3+
- Laravel 12
- Laravel Sanctum
- Laravel Reverb
- Laravel Horizon
- Laravel Telescope
- Eloquent ORM
- Redis / Predis
- SQLite, MySQL, MariaDB
- Spatie Permission
- Spatie Activitylog
- Spatie Query Builder
- Spatie Schedule Monitor
- FCM / APNs Push Notification
- Composer

## 프로젝트 구조

```text
beaulab/
├── app/
│   ├── Common/                    # 공통 응답, 예외, 권한 상수, 미들웨어
│   ├── Domains/                   # 도메인 모델, Action, Query, DTO, Policy
│   │   ├── AccountStaff/          # 뷰랩 내부 계정
│   │   ├── AccountHospital/       # 병원 파트너 계정
│   │   ├── AccountBeauty/         # 뷰티 파트너 계정
│   │   ├── AccountUser/           # 일반 사용자 계정/차단
│   │   ├── Hospital/              # 병원 도메인
│   │   ├── Beauty/                # 뷰티 업체 도메인
│   │   ├── HospitalDoctor/        # 병원 의사 도메인
│   │   ├── BeautyExpert/          # 뷰티 전문가 도메인
│   │   ├── HospitalVideo/         # 병원 영상 요청 도메인
│   │   ├── Talk/                  # 토크/댓글/저장 도메인
│   │   ├── Chat/                  # 1:1 채팅 도메인
│   │   ├── Notice/                # 공지사항 도메인
│   │   ├── Faq/                   # FAQ 도메인
│   │   └── Common/                # Media, Category, Notification, AdminNote
│   ├── Modules/                   # Actor별 HTTP 진입점
│   │   ├── Staff/                 # Staff routes/controllers/requests
│   │   ├── Hospital/              # Hospital routes/controllers/requests
│   │   ├── Beauty/                # Beauty routes/controllers/requests
│   │   └── User/                  # User routes/controllers/requests
│   └── Providers/                 # Horizon, Telescope, 내부 도구 Provider
├── bootstrap/
│   └── app.php                    # 라우팅, 미들웨어, 전역 예외 처리
├── config/
│   ├── auth.php                   # Actor별 guard/provider
│   ├── horizon.php                # Redis queue supervisor
│   ├── reverb.php                 # 실시간 브로드캐스트 서버
│   └── notification_push.php      # FCM/APNs Push 설정
├── database/
│   ├── migrations/                # 계정, 파트너, 콘텐츠, 채팅, 알림, 권한 테이블
│   ├── seeders/                   # 기본 데이터 및 권한 시더
│   └── factories/                 # 테스트/더미 데이터 factory
├── develop-doc/                   # 구조, 권한, 큐, 채팅, 알림 운영 문서
├── resources/
│   └── views/tools/               # 내부 운영 도구 로그인/허브 화면
├── routes/
│   ├── api.php                    # v1 Actor 라우트 분기
│   ├── web.php                    # 내부 도구 웹 라우트
│   ├── channels.php               # Reverb private channel 인증
│   └── console.php                # Scheduler/Artisan command
├── storage/
├── public/
├── composer.json
└── artisan
```

## 실행 준비

PHP 8.3 이상, Composer, Redis가 필요합니다. 로컬 기본 DB는 `.env.example` 기준 SQLite입니다.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan serve
```

Windows PowerShell:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan serve
```

Queue/Horizon:

```bash
php artisan horizon
```

Reverb:

```bash
php artisan reverb:start
```

Scheduler:

```bash
php artisan schedule:work
```

주요 라우트 확인:

```bash
php artisan route:list --path=api/v1
```

권한 시더 재동기화:

```bash
php artisan db:seed --class=AuthorizationSeeder
```

## 주요 환경변수

| 변수 | 설명 |
| --- | --- |
| `APP_NAME` | 애플리케이션 이름 |
| `APP_ENV` | 실행 환경 (`local`, `production`) |
| `APP_KEY` | Laravel 암호화 키 |
| `APP_URL` | 애플리케이션 기본 URL |
| `DB_CONNECTION` | DB 드라이버 (`sqlite`, `mysql`, `mariadb` 등) |
| `DB_DATABASE` | DB 이름 또는 SQLite 파일 경로 |
| `DB_HOST` | MySQL/MariaDB 호스트 |
| `DB_PORT` | MySQL/MariaDB 포트 |
| `DB_USERNAME` | DB 사용자명 |
| `DB_PASSWORD` | DB 비밀번호 |
| `SESSION_DRIVER` | 세션 저장소 (`database` 기본 사용) |
| `CACHE_STORE` | 캐시 저장소 |
| `QUEUE_CONNECTION` | 큐 드라이버 (`redis` 기본 사용) |
| `REDIS_HOST` | Redis 호스트 |
| `REDIS_PORT` | Redis 포트 |
| `BROADCAST_CONNECTION` | 브로드캐스트 드라이버 (`reverb`) |
| `REVERB_APP_ID` | Reverb App ID |
| `REVERB_APP_KEY` | Reverb App Key |
| `REVERB_APP_SECRET` | Reverb App Secret |
| `REVERB_HOST` | Reverb 접속 호스트 |
| `REVERB_PORT` | Reverb 접속 포트 |
| `REVERB_SCHEME` | Reverb 접속 프로토콜 (`http`, `https`) |
| `PUSH_ENABLED` | Push 발송 활성화 여부 |
| `PUSH_PROVIDER` | 기본 Push Provider (`fcm`) |
| `PUSH_QUEUE` | Push 발송 큐 이름 |
| `FCM_ENABLED` | FCM 발송 활성화 여부 |
| `FCM_PROJECT_ID` | FCM 프로젝트 ID |
| `FCM_SERVICE_ACCOUNT_PATH` | FCM 서비스 계정 JSON 파일 경로 |
| `FCM_SERVICE_ACCOUNT_JSON` | FCM 서비스 계정 JSON 문자열 |
| `APNS_ENABLED` | APNs 발송 활성화 여부 |
| `APNS_ENVIRONMENT` | APNs 환경 (`production`, `sandbox`) |
| `APNS_TEAM_ID` | Apple Developer Team ID |
| `APNS_KEY_ID` | APNs Key ID |
| `APNS_BUNDLE_ID` | iOS Bundle ID |
| `INTERNAL_TOOL_ALLOWED_IPS` | Horizon/Telescope 등 내부 도구 접근 허용 IP |
| `INTERNAL_TOOL_ALLOWED_EMAILS` | 내부 도구 접근 허용 Staff 이메일 목록 |
| `INTERNAL_TOOL_SWAGGER_URL` | 내부 도구 허브에서 연결할 Swagger URL |

## 확장 예정 범위

아래 항목은 서비스 범위에는 포함하지만, 현재 코드와 문서 기준으로 정책/스키마/API 설계가 더 필요한 확장 영역입니다.

- 신고/제재
  - 유저 신고
  - 게시글/댓글 신고
  - 채팅 메시지 신고
  - 후기/전후 사진 신고
  - 신고 사유 코드
  - 운영자 신고 목록/상세/처리 API
  - 블라인드, 계정 제재, 파트너 제재 연동
- 후기/검증
  - 영수증 인증 후기
  - 예약/방문 인증 후기
  - 전후 사진 후기
  - 부작용 후기
  - 후기 보상/이벤트 연동
- 상담/예약/견적
  - 상담 신청
  - 병원/뷰티 파트너 답변
  - 견적 요청/견적 답변
  - 예약 신청/변경/취소
  - 방문 완료/노쇼 상태
- 검색/추천
  - 병원/시술 통합 검색
  - AI 또는 비정형 콘텐츠 기반 검색
  - 인기 검색어
  - 사용자 관심사 기반 추천
  - 랭킹/기획전 노출 로직
- 커머스/프로모션
  - 쿠폰
  - 이벤트 가격
  - 기획전
  - 결제/환불 정책 연동
  - 광고 상품/상위 노출 정책

`develop-doc/todo.md`의 신고 기능은 이 확장 범위에 포함되며, 실제 구현 시 도메인 모델, 상태값, 권한, 운영자 처리 흐름을 별도 문서와 함께 확정합니다.

## 참고 문서

- [개발 문서 목록](develop-doc/README.md)
- [아키텍처 & 흐름](develop-doc/architecture.md)
- [권한 / 메뉴 설계](develop-doc/authorization.md)
- [도메인 & 상태 정의서](develop-doc/domain-status-definition.md)
- [채팅 설계](develop-doc/chat.md)
- [알림 설계](develop-doc/notification.md)
- [Queue 운영 가이드](develop-doc/queue.md)
- [Scheduler 운영 가이드](develop-doc/scheduler.md)
- [내부도구 허브 운영 가이드](develop-doc/internal-tools.md)
- [에러 / 예외 처리](develop-doc/error-handling.md)
- [로깅 전략](develop-doc/logging.md)

## 관련 저장소

- 백엔드 저장소: `https://github.com/xowlsakffl/beaulab_backend.git`
- 본 저장소는 뷰랩 성형·미용 중개 앱 플랫폼의 Laravel API 서버와 도메인/운영 백엔드 구성을 포함합니다.
