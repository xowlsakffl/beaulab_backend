# Frontend 유지보수 가이드 (Beaulab)

Beaulab 프론트엔드(React + TypeScript + Inertia)를 유지보수할 때 **어디를 고치면 되는지**, **어떤 변경이 위험한지**, **추가/수정 작업을 어떤 순서로 하면 되는지**를 빠르게 안내하기 위한 문서입니다.

---

## 1) 기술 스택 요약

- **React 19 + TypeScript**
- **Inertia.js (React adapter)**: 서버 기반 라우팅 + SPA UX
- **Vite**: 개발 서버/번들러
- **Tailwind CSS**: 스타일링
- **Radix UI 기반 UI 컴포넌트**: `resources/js/components/ui/*`

---

## 2) 프론트엔드 디렉터리 구조(핵심)

> 프론트 코드는 대부분 `resources/js` 아래에 위치합니다.

- `resources/js/pages/*`
    - **Inertia Page(화면 단위)**가 위치
    - 보통 라우트(페이지)와 1:1로 매칭되는 파일들
- `resources/js/layouts/*`
    - **레이아웃(헤더/사이드바/인증)** 구성
    - 페이지에서 `Page.layout = (page) => <Layout>{page}</Layout>` 패턴으로 결합
- `resources/js/components/*`
    - 앱 레벨 공통 컴포넌트(헤더, 사이드바, 브레드크럼, 로고 등)
- `resources/js/components/ui/*`
    - 버튼/시트/사이드바 등 **UI 프리미티브**
    - 전역 영향도가 높아서 변경 시 주의 필요
- `resources/js/hooks/*`
    - 공통 훅(모바일 판별, appearance 등)
- `resources/js/routes/*`, `resources/js/wayfinder/*`
    - 라우트 헬퍼/연동 유틸(프로젝트 구성에 따라 사용)

---

## 3) 레이아웃 시스템(가장 중요한 유지보수 포인트)

### 목표(팀 공통 원칙)
- **사이드바 / 헤더는 “고정(독립)”**: 페이지 이동에도 구조/상태가 유지되어야 함
- **페이지마다 바뀌는 것은 컨텐츠(children)만**: 화면 내용만 교체

### 레이아웃 관련 주요 파일
- `resources/js/layouts/admin/app-layout.tsx`
    - Admin 영역 기본 레이아웃 엔트리 역할(내부에서 다른 레이아웃을 감쌀 수 있음)
    - **사이드바 + 헤더 + 컨텐츠 조합** 핵심 레이아웃
- `resources/js/layouts/admin/auth/*`
    - 로그인/비밀번호 찾기 등 **인증 전용 레이아웃**

### 레이아웃 구성 요소(역할 분리)
- `resources/js/components/app-shell.tsx`
    - 레이아웃 최상단 Wrapper/Provider 성격
    - 레이아웃 모드(예: header형/sidebar형)를 나누는 variant 개념이 있을 수 있음
- `resources/js/components/app-sidebar.tsx`
    - 좌측 네비게이션 영역(메뉴)
- `resources/js/components/app-header.tsx`
    - 상단 헤더(검색, 유저 메뉴, 페이지 바/브레드크럼 포함)
- `resources/js/components/app-content.tsx`
    - 컨텐츠 영역 래퍼(레이아웃 variant에 따라 동작이 달라질 수 있음)

### 유지보수 규칙(권장)
- 페이지에서 “레이아웃 구조”를 직접 바꾸지 말고:
    1) 레이아웃에 필요한 값은 props로 전달(예: breadcrumbs)
    2) 레이아웃 변형이 필요하면 별도 Layout을 만들어 분기(예: SettingsLayout)
- 레이아웃/Provider/UI 프리미티브 수정은 **영향도 높음**(아래 8장 참고)

---

## 4) 페이지(Page) 추가/수정 방법

### 새 페이지 추가(권장 순서)
1. `resources/js/pages/...`에 페이지 파일 생성
2. 페이지 컴포넌트 작성
3. 페이지에 레이아웃 연결
    - 예: `Page.layout = (page) => <AppLayout>{page}</AppLayout>`
4. 메뉴에 노출이 필요하면 네비게이션 정의 파일에 추가(아래 5장 참고)
5. breadcrumbs가 필요하면 페이지에서 정의 후 Layout로 전달

### 페이지 수정 시 원칙
- 페이지는 **컨텐츠와 페이지 단위 로직**에 집중
- 헤더/사이드바/공통 UI 변경은 페이지가 아니라 **components/layouts**에서 수정

---

## 5) 네비게이션(메뉴) 수정 가이드

### 메뉴 데이터 정의
- `resources/js/components/admin-nav.ts`
    - Admin 네비게이션 아이템 정의(메뉴 트리/라벨/경로 등)

### 메뉴 렌더링
- `resources/js/components/nav-main.tsx`
    - 메뉴 UI 렌더링 및 active 상태 처리

### 메뉴 추가 체크리스트
- [ ] 링크(URL 생성 방식)가 프로젝트 라우팅 규칙과 일치하는가?
- [ ] 메뉴 텍스트/아이콘이 일관된 스타일을 따르는가?
- [ ] active(현재 위치) 표시가 정상인가?
- [ ] (권한이 있다면) 권한/역할 조건이 반영되어야 하는가?

---

## 6) UI 프리미티브(`components/ui/*`) 수정 시 주의사항

다음 파일들은 앱 전체 경험에 큰 영향을 줍니다.

- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/ui/sheet.tsx`

### 수정 원칙(권장)
- UI 프리미티브는 “스타일”뿐 아니라 **동작/접근성/키보드 인터랙션**을 포함
- 작은 수정이라도 영향범위가 크므로, 변경 후 아래를 반드시 확인:
    - Desktop: expanded/collapsed 전환
    - Mobile: sheet open/close, overlay, 스크롤
    - Header sticky + Sidebar 오버레이 겹침

---

## 7) 상태/데이터 흐름(Inertia 관점)

- 페이지 데이터는 일반적으로 **Inertia props**로 전달됩니다.
- “전역처럼 보이는 UI 상태(사이드바 열림 등)”는 다음 중 하나일 수 있습니다.
    - 서버에서 내려주는 props
    - Provider 내부 상태
    - 쿠키/스토리지 기반 유지

### 문제 해결 접근(권장)
1) “이 값은 어디서 오지?”
- `usePage().props` 계열부터 확인

2) “페이지 이동 후 상태가 유지되어야 하는데 초기화된다”
- Layout/Provider가 불필요하게 리마운트되는지 확인
- state 저장 위치(Provider/cookie 등)를 확인

---

## 8) 변경 영향도(수정 위험도) 가이드

### High Impact (주의: 앱 전체에 영향)
- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/app-shell.tsx`
- `resources/js/layouts/admin/app-layout.tsx`

### Medium
- `resources/js/components/app-header.tsx`, `app-header-bar.tsx`, `app-page-bar.tsx`
- `resources/js/components/app-sidebar.tsx`
- `resources/js/components/nav-main.tsx`

### Low (대체로 안전)
- `resources/js/pages/*`의 개별 페이지
- 단일 목적의 작은 컴포넌트(재사용 범위가 좁은 경우)

---

## 9) PR/리팩터링 체크리스트(추천)

- [ ] 페이지 이동 시 **헤더/사이드바가 리마운트되지 않고 유지**되는가?
- [ ] sticky header + sidebar/sheet의 z-index 충돌이 없는가?
- [ ] 라이트/다크 모드에서 대비/배경/테두리 깨짐이 없는가?
- [ ] 메뉴 active 상태가 정상인가?
- [ ] 공통 컴포넌트 변경 시 최소 2~3개 페이지에서 확인했는가?

---

## 10) 운영/개발 팁(권장 관례)

- “레이아웃 spacing(패딩/마진)”은 레이아웃 파일에서 관리  
  → 페이지에서 레이아웃을 다시 흔들지 않기
- 공통 컴포넌트는 “자기 영역”만 책임지도록 유지  
  → 전역 레이아웃/다른 영역 스타일에 간섭 최소화
- 재사용이 늘어나는 UI는 페이지에서 복붙하지 말고 `components/*`로 승격

---

## 11) 문서 개선을 위한 TODO (선택)

프로젝트 규칙을 더 명확히 하려면 아래 항목을 추후 보강합니다.

- [ ] Admin에서 레이아웃 예외(풀스크린, 사이드바 없는 화면) 정의
- [ ] 권한/역할 기반 메뉴 노출 정책 정리
- [ ] 상태 관리 원칙(Inertia props vs local state vs 별도 store) 명문화
- [ ] UI 변경 시 테스트 시나리오(모바일/데스크탑) 체크리스트 구체화
