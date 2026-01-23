# Authorization & Navigation 설계 (Admin / App User)

이 문서는 Beaulab 프로젝트에서 **권한(Authorization) 분기**와 **관리자 메뉴(Navigation) 분기**를 일관되게 구현하기 위한 설계 기준입니다.

본 프로젝트는 이미 다음과 같이 인증(Authentication) 레벨에서 분리되어 있습니다.

- App 사용자(`user`): Sanctum 토큰 기반 (`/api/*`)
- 관리자(`admin`): 세션 기반 (`/admin/*`, `/admin/api/*`)
    - 관리자 계정에는 **내부직원**, **병원회원**, **뷰티회원**, **대행사계정**이 포함됨
- 게스트: 입점신청, 제휴문의 가능

---

## 1. 설계 목표

1) **보안**: 메뉴를 숨기는 것은 UX일 뿐이며, 실제 접근 제어는 반드시 **라우트/API 레벨**에서 차단한다.
2) **유지보수**: “화면마다 role if문”을 최소화하고, 권한 규칙을 한 곳에서 재사용한다.
3) **확장성**: 내부직원 권한 제약(부서/직무별)을 수용할 수 있어야 한다.
4) **일관성**: 같은 규칙으로 페이지 접근/관리자 API 접근/메뉴 노출/데이터 범위(스코프)를 모두 통제한다.

---

## 2. 계정 타입/역할 모델 (Role Taxonomy)

권한 논의에서 용어 혼선을 막기 위해, **계정 타입(Account Type)** 과 **역할(Role)** 을 다음처럼 구분한다.

### 2.1 Account Type (누가 로그인하나)
- `guest` (비로그인)
- `user` (앱 사용자; Sanctum 토큰)
- `admin` (관리자; 세션)
    - 내부직원(Staff)
    - 병원회원(Hospital Member)
    - 뷰티회원(Beauty Member)
    - 대행사계정(Agency)

> 이 문서의 “권한/메뉴”는 주로 `admin` 영역(`/admin/*`, `/admin/api/*`)을 대상으로 한다.

### 2.2 Admin Role (admin 내부 분류)
Admin 계정은 다음 Role 중 하나를 가진다(“규칙 기반(하드코딩)”을 전제).

- 내부직원(Staff)
    - 최고관리자 (Super Admin)
    - 관리직원 (Operations/Admin Staff)
    - 개발자 (Developer)
- 병원회원(Hospital Member)
    - 지점장 (Manager)
    - 직원 (Staff)
- 뷰티회원(Beauty Member)
    - 지점장 (Manager)
    - 직원 (Staff)
- 대행사계정(Agency)

> 원칙: **Role은 정체성/조직상의 분류**이고, 실제 접근 제어는 가능하면 Ability(기능 권한)로 표현한다.

---

## 3. 핵심 개념: Ability(기능)와 Scope(데이터 범위)의 분리

권한을 다음 두 축으로 분리한다.

### 3.1 Ability (기능 권한)
“무엇을 할 수 있나?”를 의미한다. 예:
- `dashboard.read`
- `hospital.read`, `hospital.update`
- `lead.read`, `lead.update` (예약/상담 유입 고객)
- `report.read`
- `admin_user.manage` (내부직원 계정/권한 관리)

> Ability는 라우트/API 접근 제어, 메뉴 노출 제어에 직접 사용한다.

### 3.2 Scope (데이터 범위)
“어느 데이터까지 할 수 있나?”를 의미한다.

- 내부직원: 기본적으로 `scope = all`이 가능하나, **내부직원도 ability가 제약**되므로 “all 스코프 + ability 제한” 조합으로 운용한다.
- 병원회원: `scope = own_hospital`
- 뷰티회원: `scope = own_beauty_store` (용어는 실제 도메인 명칭에 맞춰 확정)
- 대행사계정: `scope = assigned_accounts` (대행이 맡은 병원/뷰티 계정 범위)

#### 스코프 규칙(확정된 것)
- 병원회원: “한 병원에 여러 회원은 가능하지만, 한 회원이 여러 병원 소속은 불가”
    - 따라서 병원회원의 범위는 항상 **자기 병원(own_hospital)** 에 한정된다.

#### 스코프 규칙(추후 확정이 필요한 것)
- 뷰티회원의 소속 단위(지점/매장/브랜드) 명칭 및 관계
- 대행사계정의 “대행 범위”를 어떤 관계로 관리할지(담당 병원/담당 뷰티/담당 이벤트 등)

---

## 4. 도메인 정의: “고객”의 범위(확정)

관리자에서 다루는 “고객”은 다음으로 한정한다.

- 고객(Lead/Customer)은 **예약/상담을 통해 유입된 고객만**을 의미한다.
- 병원/뷰티가 임의로 등록한 “자체 고객 DB”는 이 범위에 포함하지 않는다. (추후 도메인 확장 시 별도 정의)
- 병원 자체 관리 시스템이 있는 경우 데이터 API 전송 검토

이 규칙은 목록 조회/상세 조회/리포트 집계 등 모든 기능에 동일하게 적용한다.

---

## 5. 권한 결정 방식: 규칙 기반(하드코딩)

권한은 DB에서 동적으로 구성하지 않고, **규칙 기반**으로 정의한다.

- 내부직원도 “전부 슈퍼 권한”이 아니라, **직무(Role)에 따라 ability를 제한**한다.
- 병원/뷰티 회원은 “운영에 필요한 ability + 소속 스코프(own_*)” 조합으로 제한한다.
- 대행사계정은 “대행 업무 ability + assigned 스코프” 조합으로 제한한다.

> 규칙 기반이므로, “권한 부여/회수”는 코드 변경(설정/Enum/Policy/Gate)으로 이루어진다.

---

## 6. Admin 영역 권한 체크 규칙

### 6.1 라우트/API 접근 제어
- `/admin/*` (Inertia 페이지)와 `/admin/api/*` (JSON API)는 모두 `auth:admin`을 전제한다.
- 그 다음 단계는 **Ability 기반**으로 접근을 차단한다.
    - 예: `report.read` 없으면 `/admin/reports` 및 `/admin/api/reports` 접근 불가(403)

### 6.2 데이터 접근 제어(스코프)
특정 리소스(병원/뷰티, 유입고객, 예약/상담 등) 단위 접근은 다음 원칙을 따른다.

- 내부직원: Ability가 있으면 접근 가능(단, 직무에 따라 ability 자체가 제한됨)
- 병원/뷰티 회원: Ability가 있어도 **자기 소속(own_*) 범위만** 접근 가능
- 대행사계정: Ability가 있어도 **assigned 범위만** 접근 가능

> 결론: “기능은 ability로”, “범위는 scope로” 나눈다.

---

## 7. 메뉴 분기(Navigation) 규칙

### 7.1 메뉴 노출은 ability 결과로 결정한다
메뉴는 role(내부직원/병원회원/뷰티회원/대행사)로 직접 분기하지 않고, 가능한 한 **ability 기반으로 노출**한다.

- 서버는 로그인한 admin에 대해 “가능한 abilities”를 계산한다.
- 프론트는 abilities 결과로 메뉴를 필터링한다.
- 메뉴를 숨긴다고 해서 접근이 막히는 것이 아니며, 접근 제어는 6장 규칙으로 별도 강제한다.

### 7.2 메뉴 정의는 “데이터”로 한 곳에 모은다
각 메뉴 항목은 다음 정보를 가진다.

- label / route / group / icon (UI)
- required abilities (권한)
- (필요한 경우에만) role 제한

원칙적으로 role 제한은 최소화하고,
정말 “특정 그룹만 가능한 기능”은 해당 기능을 나타내는 ability로 표현한다.

---

## 8. Ability 네이밍 규칙(권장)

형식:
- `{domain}.{action}`

예시:
- `dashboard.read`
- `hospital.read`, `hospital.update`
- `lead.read`, `lead.update`
- `report.read`
- `settlement.read` (정산이 생기면)

Action은 다음 중에서 우선 선택:
- `read`, `create`, `update`, `delete`, `export`, `manage`

---

## 9. 역할(Role)과 권한(Ability) 매핑(초안)

리소스/메뉴의 최종 구성은 추후 확정하지만, “매핑 문서 구조”는 아래처럼 유지한다.

### 9.1 병원회원(Hospital Member)
- scope = own_hospital
- 지점장/직원 차이는 ability로 표현(예: 직원은 일부 update 불가 등)

예(초안):
- `dashboard.read`
- `hospital.read`, `hospital.update` (own_hospital)
- `lead.read`, `lead.update` (own_hospital)
- `report.read` (own_hospital)

### 9.2 뷰티회원(Beauty Member)
- scope = own_beauty_store
- 지점장/직원 차이는 ability로 표현

예(초안):
- `dashboard.read`
- `beauty_store.read`, `beauty_store.update` (own_beauty_store)
- `lead.read`, `lead.update` (own_beauty_store)
- `report.read` (own_beauty_store)

### 9.3 대행사계정(Agency)
- scope = assigned_accounts
- “대행 범위” 안에서만 조회/수정 가능

예(초안):
- `dashboard.read`
- `lead.read`, `lead.update` (assigned)
- `report.read` (assigned)

### 9.4 내부직원(Staff)
- ability는 직무(Role)별로 제한됨
- 최고관리자(Super Admin)는 광범위한 ability를 가질 수 있음(단, 규칙 기반으로 정의)

예(초안):
- 관리직원: `hospital.read`, `hospital.update`, `lead.read`, `lead.update`, `report.read`
- 개발자: 운영 기능 접근은 원칙적으로 최소화하고, 필요 시 별도 ability로 제한(예: `system.tools`)
- 최고관리자: `admin_user.manage` 포함

> 내부직원 직무별 매핑표는 “리소스 Top 5”가 확정되는 시점에 구체화한다.

---

## 10. 구현 체크리스트(설계 준수 여부)

- [ ] `/admin/*`, `/admin/api/*`는 `auth:admin`이 필수다.
- [ ] 모든 관리자 주요 페이지/API는 대응하는 ability 체크가 존재한다.
- [ ] 병원/뷰티 회원은 어떤 엔드포인트를 호출해도 “자기 소속(own_*)” 밖 데이터는 반환되지 않는다.
- [ ] 대행사계정은 어떤 엔드포인트를 호출해도 “assigned 범위” 밖 데이터는 반환되지 않는다.
- [ ] 메뉴는 프론트 조건문 난립이 아니라, ability 기반 필터링으로 구성된다.
- [ ] “고객(lead)”의 정의(예약/상담 유입만)가 목록/상세/리포트에 동일하게 적용된다.

---

작성 기준: 2026-01-23
