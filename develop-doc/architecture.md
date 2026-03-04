# Architecture (구조 / 흐름)

이 문서는 현재 코드 기준으로 Beaulab 백엔드 구조를 정리합니다.
핵심은 **Actor(Staff/Hospital/Beauty/User) 진입점**과 **Domain 비즈니스 로직**을 분리하는 것입니다.

---

## 1) API 엔드포인트 구성

`routes/api.php`에서 v1 라우트를 Actor 단위로 분기합니다.

- Staff API: `/api/v1/staff/*`
- Hospital API: `/api/v1/hospital/*`
- Beauty API: `/api/v1/beauty/*`
- User API: `/api/v1/user/*`

> 실제 상세 라우트는 각 모듈 파일(`app/Modules/*/routes/api_*.php`)에서 관리합니다.

---

## 2) 실제 디렉토리 구조 (현재 구현 기준)

- `app/Modules/Staff/*`
  - Staff 전용 컨트롤러/요청 검증/라우트
- `app/Modules/Hospital/*`
  - Hospital 전용 컨트롤러/요청 검증/라우트
- `app/Modules/Beauty/*`
  - Beauty 전용 컨트롤러/요청 검증/라우트
- `app/Modules/User/*`
  - User 전용 라우트(현재 최소 구성)
- `app/Domains/*`
  - 도메인 모델/액션/쿼리/정책
  - 현재 주요 도메인: `Hospital`, `Beauty`, `HospitalDoctor`, `BeautyExpert`, `Account*`, `Common`
- `app/Common/*`
  - 공통 응답, 예외, 권한 정의, 미들웨어

---

## 3) 요청 처리 원칙

1. **Module Controller**
   - Request Validation
   - 인증/인가 미들웨어 통과
   - Domain Action 호출
2. **Domain Action/Query**
   - 비즈니스 규칙 처리
   - 조회/상태 변경
3. **공통 응답 반환**
   - `ApiResponse` 포맷으로 일관 반환

---

## 4) Staff API 인증/인가 흐름 (현재 운영 중)

- Staff 보호 라우트
    - `auth:sanctum`
    - `abilities:actor:staff`
- Hospital 보호 라우트
    - `auth:sanctum`
    - `abilities:actor:hospital`
- Beauty 보호 라우트
    - `auth:sanctum`
    - `abilities:actor:beauty`
- User
    - 현재 라우트 뼈대만 존재(추가 구현 예정)
---

## 5) 기능 구현 상태 (현재 코드 기준)

- Staff
    - 인증, 프로필/비밀번호 수정
    - 병원/뷰티/회원/의사/뷰티전문가 CRUD
    - 영상요청(목록/상세/수정/삭제)
- Hospital
    - 인증, 프로필/비밀번호 수정
    - 영상요청(목록/상세/생성/수정/취소)
- Beauty
    - 인증, 프로필/비밀번호 수정
    - 영상요청 라우트는 아직 미노출(추가 예정)
- User
    - 모듈 경로만 존재, 실질 API는 미구현
---

## 6) 도메인 계층 원칙

- 모델은 `app/Domains/{Domain}/Models`에 둔다.
- 컨트롤러는 얇게 유지하고 복잡한 비즈니스 로직은 `Actions`/`Queries`로 이동한다.
- Actor별 차이가 필요한 경우 `Actions/Staff`, `Actions/Hospital`, `Actions/Beauty`, `Actions/User`로 분리한다.

---

## 7) 체크리스트

- [ ] 새 모델을 `Domains/*/Models`에 생성했는가?
- [ ] 컨트롤러에 비즈니스 로직이 과도하게 들어가지 않았는가?
- [ ] Permission/Policy 기준으로 접근 제어를 적용했는가?
- [ ] 응답 포맷을 `ApiResponse`로 통일했는가?

---

작성 기준: 2026-03-04 (코드 스냅샷 반영)
