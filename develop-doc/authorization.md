# Authorization & Navigation 설계 (Staff / Partner / User)

이 문서는 Beaulab 프로젝트에서 **권한(Authorization) 분기**와  
**메뉴/기능 노출(Navigation) 분기**를 일관되게 구현하기 위한 설계 기준입니다.

본 프로젝트는 **API-only 구조**를 전제로 하며,  
Laravel은 인증/인가 및 비즈니스 규칙을 담당하고  
UI는 외부 프론트엔드(Web / Mobile)에서 처리합니다.

---

## 1. 인증(Authentication) 레벨 구분

프로젝트의 인증 레벨은 다음과 같이 구분됩니다.

- **User**: 일반 사용자
    - Sanctum 토큰 기반
    - `/api/v1/user/*`
- **Partner**: 병원 / 뷰티 / 대행사 계정
    - Sanctum 토큰 기반
    - `/api/v1/partner/*`
- **Staff**: Beaulab 내부 직원
    - Sanctum 토큰 기반
    - `/api/v1/staff/*`
- **Guest**: 비로그인 사용자
    - 입점 신청 / 제휴 문의 등 제한된 공개 기능

> ❌ 세션 기반 인증, `/admin/*`, Fortify 기반 인증은 사용하지 않는다.

---

## 2. 설계 목표

1) **보안**  
   메뉴 노출은 UX일 뿐이며, 실제 접근 제어는 반드시 **API/Policy 레벨**에서 차단한다.

2) **유지보수성**  
   화면/컨트롤러에 role if 분기를 두지 않고,  
   **권한 규칙을 단일 소스(Common Authorization)** 로 관리한다.

3) **확장성**  
   내부 직원, 파트너(병원/뷰티/대행사), 사용자 확장을 전제로 한다.

4) **일관성**  
   API 접근, 메뉴 노출, 데이터 범위를 **같은 Permission / Scope 규칙**으로 통제한다.

---

## 3. 권한 모델 개요 (Role / Permission / Scope)

권한은 다음 3요소로 분리한다.

- **Role(역할)**  
  계정의 정체성(권한 템플릿)

- **Permission(기능 권한)**  
  무엇을 할 수 있는가 (Ability)

- **Scope(데이터 범위)**  
  어느 데이터까지 접근 가능한가

원칙:
- 기능 접근 제어 = **Permission**
- 데이터 범위 제어 = **Scope + Policy / Query Scoping**
- 메뉴 노출은 role이 아니라 **required permissions** 기준으로 처리한다.
- Scope는 Spatie Permission으로 해결하지 않고 **도메인 규칙으로 고정**한다.

---

## 4. 계정 타입 / 역할 모델 (Role Taxonomy)

### 4.1 Account Type (누가 로그인하는가)

- `guest`
- `user` (일반 사용자)
- `partner`
    - 병원(Hospital)
    - 뷰티(Beauty)
    - 대행사(Agency)
- `staff` (Beaulab 내부 직원)

> 이 문서의 권한/메뉴 규칙은 **Staff / Partner API**를 중심으로 한다.

---

### 4.2 Role 네이밍 규칙 (Spatie Role)

Role 이름은 **소속(prefix) + 역할**로 정의한다.

- 내부직원(Staff)
    - `beaulab.super_admin`
    - `beaulab.admin`
    - `beaulab.staff`
    - `beaulab.dev`

- 병원(Partner)
    - `hospital.owner`
    - `hospital.manager`
    - `hospital.staff`

- 뷰티(Partner)
    - `beauty.owner`
    - `beauty.manager`
    - `beauty.staff`

- 대행사(Partner)
    - `agency.owner`
    - `agency.staff`

---

## 5. 도메인 정의: “고객(Lead)” 범위 (확정)

- 고객(Lead)은 **예약/상담을 통해 유입된 사용자**만을 의미한다.
- 병원/뷰티가 임의로 등록한 자체 고객 DB는 포함하지 않는다.
- 추후 확장 시 별도 도메인으로 정의한다.

---

## 6. Scope 표 (데이터 범위) (확정)

Scope는 **도메인 규칙으로 고정**하며 Policy/Query에서 강제한다.

| Scope 코드 | 적용 대상 | 범위 정의 |
|---|---|---|
| `PUBLIC` | Guest | 공개 페이지/폼 |
| `OWN_HOSPITAL` | 병원 계정 | 자기 병원 데이터만 |
| `OWN_BEAUTY` | 뷰티 계정 | 자기 뷰티 데이터만 |
| `ASSIGNED_ACCOUNTS` | 대행사 | 할당된 병원/뷰티 범위 |
| `ALL` | 내부 직원 | 전체 데이터 |

---

## 7. Permission(Ability) 네이밍 규칙 (확정)

형식:
- `{domain}.{resource}.{action}`

규칙:
- Permission은 **기능(Ability)** 만 표현한다.
- 데이터 범위는 Scope / Policy / Query에서 제어한다.
- 권한 정의의 단일 소스는  
  `app/Common/Authorization/AccessPermissions.php` 이다.

---

## 8. Permission 목록
(`AccessPermissions` 기준)

### 8.1 공통(Common)

| Permission | 설명 |
|---|---|
| `common.access` | 기본 접근 |
| `common.dashboard.show` | 대시보드 조회 |
| `common.profile.show` | 내 프로필 조회 |
| `common.profile.update` | 내 프로필 수정 |

---

### 8.2 Beaulab 내부직원(Staff)

#### 병원 관리
- `beaulab.hospital.list`
- `beaulab.hospital.show`
- `beaulab.hospital.create`
- `beaulab.hospital.update`

#### 뷰티 관리
- `beaulab.beauty.list`
- `beaulab.beauty.show`
- `beaulab.beauty.create`
- `beaulab.beauty.update`

#### 대행사 관리
- `beaulab.agency.list`
- `beaulab.agency.show`
- `beaulab.agency.create`
- `beaulab.agency.update`

---

### 8.3 병원(Hospital)
- `hospital.profile.show`
- `hospital.profile.update`
- `hospital.members.manage`

---

### 8.4 뷰티(Beauty)
- `beauty.profile.show`
- `beauty.profile.update`
- `beauty.members.manage`

---

### 8.5 대행사(Agency)
- `agency.profile.show`
- `agency.profile.update`
- `agency.members.manage`

---

## 9. Role 표
(`AccessRoles` 기준)

### 9.1 내부직원(Staff)

| Role | 설명 | Scope |
|---|---|---|
| `beaulab.super_admin` | 최고 관리자 | ALL |
| `beaulab.admin` | 관리자 | ALL |
| `beaulab.staff` | 일반 직원 | ALL |
| `beaulab.dev` | 개발자 | ALL |

---

### 9.2 병원(Hospital)

| Role | 설명 | Scope |
|---|---|---|
| `hospital.owner` | 병원 소유주 | OWN_HOSPITAL |
| `hospital.manager` | 병원 매니저 | OWN_HOSPITAL |
| `hospital.staff` | 병원 직원 | OWN_HOSPITAL |

---

### 9.3 뷰티(Beauty)

| Role | 설명 | Scope |
|---|---|---|
| `beauty.owner` | 뷰티 소유주 | OWN_BEAUTY |
| `beauty.manager` | 뷰티 매니저 | OWN_BEAUTY |
| `beauty.staff` | 뷰티 직원 | OWN_BEAUTY |

---

### 9.4 대행사(Agency)

| Role | 설명 | Scope |
|---|---|---|
| `agency.owner` | 대행사 소유주 | ASSIGNED_ACCOUNTS |
| `agency.staff` | 대행사 직원 | ASSIGNED_ACCOUNTS |

---

## 10. Role × Permission 매핑 원칙

> 실제 부여 기준은 `AccessRoles` 및 Seeder를 단일 기준으로 한다.

원칙:
- `beaulab.super_admin` : 모든 Permission
- `beaulab.admin` : 내부직원 전체 기능
- `beaulab.staff` / `beaulab.dev` : 조회 중심

- `hospital.*` Role : `hospital.*` Permission만
- `beauty.*` Role : `beauty.*` Permission만
- `agency.*` Role : `agency.*` Permission만

---

## 11. API 권한 체크 규칙 (구현 원칙)

### 11.1 API 접근 제어
- 모든 보호 API는 `auth:sanctum` 전제
- 그 다음 단계에서 Permission 기반으로 차단한다.

### 11.2 데이터 접근 제어 (Scope)
- 내부 직원: Permission 기준
- 파트너 계정: Scope 기준으로 소속 데이터만 접근 가능

---

## 12. 메뉴 / 기능 노출 규칙

- 메뉴 노출은 **required permissions 기준**으로 처리한다.
- 메뉴는 UX이며, 보안은 API/Policy에서 강제한다.

---

## 13. 운영 원칙

- 모든 계정은 최소 1개 Role을 가진다.
- 기본은 Role 템플릿으로 부여한다.
- Permission 이름은 문서와 코드에서 1:1로 유지한다.

---

작성 기준: 2026-01-23
