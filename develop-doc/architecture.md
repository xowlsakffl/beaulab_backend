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

- 계정: `AccountStaff`, `AccountHospital`, `AccountBeauty`, `AccountUser`
- 파트너: `Hospital`, `Beauty`, `HospitalDoctor`, `BeautyExpert`
- 콘텐츠: `Talk`, `TalkComment`, `Notice`, `Faq`
- 공통: `Media`, `Category`, `AdminNote`

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

## 7) 비동기 구조 연결

비동기 처리는 API 계층과 분리되어 동작한다.

1. API/Action에서 Job dispatch
2. Redis Queue 적재
3. Horizon 워커 처리

운영 상세는 아래 문서 참고:

- Queue: `./queue.md`
- Scheduler: `./scheduler.md`

## 8) 구현 상태 요약

- Staff
  - 인증, 프로필/비밀번호 수정
  - 병원/뷰티/회원/의사/전문가 CRUD
  - 토크/토크댓글 관리
  - 공지사항 관리
  - FAQ 관리
- Hospital
  - 인증, 프로필/비밀번호 수정
  - 영상요청 생성/조회/수정/취소
- Beauty
  - 인증, 프로필/비밀번호 수정
  - 파트너 기능 일부 추가 진행 중
- User
  - 모듈 경로 존재, API는 최소 구성

## 9) 체크리스트

- [ ] 새 API가 Actor 경계에 맞게 배치됐는가?
- [ ] 컨트롤러가 얇게 유지되고 비즈니스 로직이 Domain으로 내려갔는가?
- [ ] 정책/권한/시더가 함께 갱신됐는가?
- [ ] 비동기 작업이 lane 정책(`critical`, `mail`, `sms`, `chat`, `default` 등)에 맞게 라우팅됐는가?

작성 기준: 2026-03-12
