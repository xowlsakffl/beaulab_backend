# Architecture (구조 / 흐름)

이 문서는 Beaulab 프로젝트의 **전체 구조와 요청 흐름**을 설명합니다.  
본 프로젝트는 **Laravel API 서버 + 외부 프론트엔드(웹/모바일)** 구조를 전제로 하며,  
Actor(Staff / Partner / User)와 Domain(비즈니스 규칙)을 **명확히 분리**하는 것을 목표로 합니다.

---

## 1. 큰 그림

본 프로젝트는 아래 **3가지 Actor 기반 API 영역**으로 구성됩니다.

| 구분 | 목적 | URL Prefix | 응답 |
|---|---|---|---|
| Staff API | 내부 직원용 관리 기능 | `/api/v1/staff/*` | JSON |
| Partner API | 병원/뷰티/대행사 파트너 관리 | `/api/v1/partner/*` | JSON |
| User API | 일반 사용자 서비스 | `/api/v1/user/*` | JSON |

### 핵심 원칙
- Laravel은 **API 서버 역할만 담당**한다.
- 모든 클라이언트(UI)는 외부 프론트엔드에서 처리한다.
- 서버는 **상태를 가지지 않는(stateless) JSON API**만 제공한다.
- 모든 API 응답은 **공통 ApiResponse 규칙**을 따른다.

---

## 2. 백엔드 디렉토리 구조

- `app/Modules/Staff/*`
    - 내부 직원(Staff) API 진입점
- `app/Modules/Partner/*`
    - 파트너(병원/뷰티/대행사) API 진입점
- `app/Modules/User/*`
    - 일반 사용자 API 진입점
- `app/Domains/*`
    - **업무 도메인(비즈니스 로직)**
    - 예: `Hospital`, `Review`, `Beauty`, `Reservation` 등
    - 모델 / 액션 / 정책 / 쿼리 등 **규칙과 상태의 중심**
- `app/Common/*`
    - 전역 공통 요소
    - ApiResponse, ErrorCode, Authorization, Middleware, Exception 처리 등
- `app/Modules/*/routes/api_{actor}.php`
    - Actor별 API 라우트 정의

---

### 역할 요약
- `Modules/*`
    - HTTP/API 진입점
    - 인증/인가 적용
    - 요청 → Domain Action 연결
- `Domains`
    - **비즈니스 규칙의 중심**
    - 모델 / 유스케이스 / 정책 / 쿼리
- `Common`
    - 공통 규칙
    - 응답 포맷, 예외 처리, 권한 정의, 미들웨어

---

## 3. 핵심 설계 원칙 (중요)

### 3.1 “모델은 무조건 Domain”
- 모든 Eloquent Model은 `app/Domains/{Domain}/Models` 아래에 위치한다.
- `Modules/Staff`, `Modules/Partner`, `Modules/User`에는 모델을 두지 않는다.

> Staff / Partner / User는 “누가 호출하느냐(Actor)”의 차이이고,  
> Hospital / Review 같은 “무엇을 다루느냐(Domain)”가 비즈니스 규칙의 주인공이다.

---

### 3.2 컨트롤러는 얇게, 비즈니스 로직은 Domain Action으로
컨트롤러는 아래 역할만 담당한다.
- Request validation
- 인증 / 인가 통과 여부
- Domain Action 호출
- ApiResponse 반환

컨트롤러에서 **금지**
- 복잡한 쿼리 작성
- 트랜잭션/상태 변경 로직
- 권한 분기 if 문

---

### 3.3 Actor 차이는 Action / DTO로 분리
같은 도메인을 다루더라도 Actor에 따라 다음이 달라질 수 있다.
- 조회 조건
- 허용 필터/정렬
- 노출 필드

따라서 Domain 내부에서 다음을 분리한다.
- `Actions/Staff/*`
- `Actions/Partner/*`
- `Actions/User/*`
- `Dto/Staff/*`
- `Dto/Partner/*`
- `Dto/User/*`

---

### 3.4 공통 쿼리/로직은 Query / Support로 분리
Action 간 중복이 발생하면 아래 레이어로 흡수한다.
- `Queries/*` : 검색 / 필터 / 정렬 / 페이징
- `Supports/*` : 도메인 공통 계산, 변환, 보조 로직

---

## 4. 메소드 / 클래스 명명 규칙

### 4.1 Controller 메소드 (API 진입점)

패턴:
- 동사 + 명사 + 액터 형태
- HTTP 메서드로 역할을 구분

예:
- getHospitalsForStaff()

---

## 5. 도메인 디렉토리 템플릿 (예: Hospital)

권장 기본 형태:

- `app/Domains/Hospital/Models/Hospital.php`
- `app/Domains/Hospital/Actions/Staff/HospitalListForStaffAction.php`
- `app/Domains/Hospital/Dto/Staff/HospitalForStaffDto.php`

---

## 6. 인증 (Authentication)

### 공통 원칙
- 인증 방식: **Sanctum 토큰 기반**
- API는 stateless

### Actor별
- Staff / Partner / User 모두 동일한 토큰 인증 방식
- Actor 구분은
    - 토큰 발급 시 actor 정보 포함
    - `actor:*` 미들웨어로 검증

---

## 7. 응답 / 예외 처리 기준

### 7.1 응답 타입
- 모든 API는 **ApiResponse 포맷**으로 응답한다.

### 7.2 예외 처리 원칙
- 예외 처리는 `bootstrap/app.php`에서 단일 기준으로 처리한다.
- ErrorCode → HTTP Status → ApiResponse 매핑을 고정한다.

자세한 내용은 `error-handling.md`를 참고한다.

---

## 8. 실수 방지 체크리스트

- [ ] 새로운 모델을 `Domains/*/Models`에 만들었는가?
- [ ] 컨트롤러에 비즈니스 로직이 들어가 있지 않은가?
- [ ] Actor 차이가 Action / DTO로 분리되어 있는가?
- [ ] API 응답이 ApiResponse 규칙을 따르는가?
- [ ] 권한 정의를 Common Authorization 기준으로 사용하고 있는가?

---

작성자: 안민성
