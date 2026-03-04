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

Staff 라우트는 아래 미들웨어를 공통으로 사용합니다.

- `auth:sanctum`
- `abilities:actor:staff`
- `permission:common.access`

그 위에서 기능별로 병원/뷰티/회원/의사/뷰티전문가 관리 API를 제공합니다.

---

## 5) Partner/User 영역 상태

- Partner 라우트는 별도 파일에서 정의되어 있으며, 현재 코드상 `web`, `auth:admin`, `permission:*` 미들웨어 구조를 사용 중입니다.
- User 라우트 파일은 현재 뼈대만 존재합니다.

즉, Staff API가 가장 완성도가 높고 Partner/User는 단계적으로 확장 중인 상태입니다.

---

## 6) 도메인 계층 원칙

- 모델은 `app/Domains/{Domain}/Models`에 둔다.
- 컨트롤러는 얇게 유지하고 복잡한 비즈니스 로직은 `Actions`/`Queries`로 이동한다.
- Actor별 차이가 필요한 경우 `Actions/Staff`, `Actions/Partner`, `Actions/User`로 분리한다.

---

## 7) 체크리스트

- [ ] 새 모델을 `Domains/*/Models`에 생성했는가?
- [ ] 컨트롤러에 비즈니스 로직이 과도하게 들어가지 않았는가?
- [ ] Permission/Policy 기준으로 접근 제어를 적용했는가?
- [ ] 응답 포맷을 `ApiResponse`로 통일했는가?

---

작성 기준: 2026-02-26 (코드 스냅샷 반영)
