# Frontend 유지보수 가이드 (Beaulab)

이 문서는 Beaulab 프로젝트의 **프론트엔드(Web / Mobile)** 를 유지보수할 때  
어디를 고치면 되는지, 어떤 변경이 위험한지,  
그리고 **작업 순서를 어떻게 가져가야 하는지**를 빠르게 이해하기 위한 가이드입니다.

본 프로젝트의 프론트엔드는 **Laravel과 완전히 분리된 구조**를 전제로 합니다.

---

## 1) 프론트엔드 구조 개요

Beaulab 프론트엔드는 다음과 같이 **역할별로 분리**됩니다.

- **Staff Web**
    - 내부 직원용 관리 웹
    - 기술 스택: React 또는 Next.js
- **Partner Web**
    - 병원 / 뷰티 / 대행사 관리 웹
    - 기술 스택: React 또는 Next.js
- **User Web**
    - 일반 사용자 웹
    - 기술 스택: Next.js
- **Mobile App**
    - React Native

공통 원칙:
- 프론트엔드는 **API 서버(Laravel)** 와 완전히 분리
- 인증은 **Sanctum 토큰 기반**
- UI 라우팅은 **프론트엔드 라우터**가 책임진다

---

## 2) 기술 스택 요약 (Web 기준)

- **React + TypeScript**
- **Next.js (권장)**
    - App Router 기반
    - 서버 사이드 렌더링 / SEO 대응
- **Axios / Fetch**
    - API 통신
- **Tailwind CSS**
- **Radix UI 기반 UI 컴포넌트**

---

## 3) 프론트엔드 디렉터리 구조 (권장)

Next.js 기준 예시:

- `app/`
    - 라우트 단위 페이지
- `components/`
    - 공통 UI 컴포넌트
- `components/ui/`
    - 버튼 / 모달 / 시트 등 UI 프리미티브
- `features/`
    - 도메인 단위 UI/로직 묶음
- `layouts/`
    - 레이아웃 구성 (Header / Sidebar)
- `hooks/`
    - 공통 훅 (auth, permissions, media query 등)
- `lib/`
    - api client, utils, constants
- `stores/`
    - 전역 상태 (필요한 경우만)

---

## 4) 레이아웃 시스템 (가장 중요한 유지보수 포인트)

### 핵심 원칙
- **Header / Sidebar는 고정**
- 페이지 이동 시 레이아웃은 **리마운트되지 않는다**
- 바뀌는 것은 **컨텐츠 영역만**

### 레이아웃 구성 예
- `layouts/AppLayout.tsx`
    - Header + Sidebar + Content
- `layouts/AuthLayout.tsx`
    - 로그인 / 인증 관련 화면
- `layouts/BlankLayout.tsx`
    - 풀스크린 / 예외 페이지

### 유지보수 규칙
- 페이지에서 레이아웃 구조를 직접 바꾸지 않는다
- 레이아웃 변형이 필요하면 **새 Layout을 추가**
- spacing / grid / padding은 **Layout 책임**

---

## 5) 페이지(Page) 추가 / 수정 가이드

### 새 페이지 추가 순서
1. 라우트(app/)에 페이지 추가
2. 페이지 컴포넌트 작성
3. 필요한 Layout 적용
4. API 연동
5. 권한 체크(아래 6번)

### 페이지 작성 원칙
- 페이지는 **화면 조합 + API 호출**만 담당
- 비즈니스 규칙 판단은 서버에서 처리
- 권한 분기는 **Permission 기반**으로 처리

---

## 6) 권한 / 메뉴 분기 규칙 (프론트)

### 권한 체크 방식
- 로그인 시 서버에서 내려준:
    - permissions[]
    - role
    - scope
- 프론트에서는:
    - **Permission 기준으로만 분기**

### 메뉴 노출 원칙
- 메뉴는 UX
- 실제 보안은 **API에서 강제**
- 메뉴는 `requiredPermissions` 기준으로 노출

---

## 7) API / 상태 흐름

### 데이터 흐름 원칙
- 모든 데이터는 API에서 가져온다
- 전역처럼 보이는 상태는 최소화

상태 종류 예:
- 인증 정보: auth store
- 사이드바 열림 여부: local UI state
- 필터/정렬: URL query 또는 page state

### 문제 해결 접근 순서
1. API 응답 확인
2. auth/permission 상태 확인
3. layout 리마운트 여부 확인
4. state 위치(local / global) 확인

---

## 8) UI 프리미티브 수정 시 주의사항

다음 영역은 **변경 영향도가 매우 큼**:

- `components/ui/*`
- 공통 Modal / Sheet / Sidebar
- Header / Sidebar 레이아웃 컴포넌트

수정 시 반드시 확인:
- Desktop / Mobile
- 키보드 접근성
- z-index / overlay 충돌
- 스크롤 잠금 여부

---

## 9) 변경 영향도 가이드

### High Impact
- Layout
- Sidebar / Header
- UI primitives

### Medium
- 공통 컴포넌트
- Navigation 로직

### Low
- 개별 페이지
- 도메인 전용 컴포넌트

---

## 10) PR / 리팩터링 체크리스트

- [ ] 페이지 이동 시 레이아웃이 유지되는가?
- [ ] 권한 없는 메뉴/버튼이 노출되지 않는가?
- [ ] 모바일에서 터치 영역/스크롤 문제가 없는가?
- [ ] API 에러 처리(ApiResponse 기준)가 일관적인가?
- [ ] 공통 컴포넌트 수정 시 여러 화면에서 확인했는가?

---

## 11) 운영 관례 (권장)

- “급한 UI 수정”이라도 공통 컴포넌트는 신중히
- 재사용이 보이면 features/components로 승격
- 임시 분기는 TODO 주석으로 남기고 제거 일정 공유

---

작성 기준: 2026-01-23
