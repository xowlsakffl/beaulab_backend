# Authorization & Navigation 설계 (Admin / App User)

이 문서는 Beaulab 프로젝트에서 **권한(Authorization) 분기**와 **관리자 메뉴(Navigation) 분기**를 일관되게 구현하기 위한 설계 기준입니다.

프로젝트 인증(Authentication) 레벨은 다음과 같이 분리되어 있습니다.

- App 사용자(`user`): Sanctum 토큰 기반 (`/api/*`)
- 관리자(`admin`): 세션 기반 (`/admin/*`, `/admin/api/*`)
    - 관리자 계정에는 **내부직원**, **병원회원**, **뷰티회원**, **대행사계정**이 포함됨
- 게스트: 입점신청, 제휴문의 가능

---

## 1. 설계 목표

1) **보안**: 메뉴 숨김은 UX일 뿐이며, 실제 접근 제어는 반드시 **라우트/API 레벨**에서 차단한다.
2) **유지보수**: “화면마다 role if문”을 최소화하고, 권한 규칙을 한 곳에서 재사용한다.
3) **확장성**: 내부직원 권한 제약(부서/직무별)을 수용한다.
4) **일관성**: 같은 규칙으로 페이지 접근/관리자 API 접근/메뉴 노출/데이터 범위(스코프)를 통제한다.

---

## 2. 권한 모델 개요 (Role / Permission / Scope)

권한을 다음 3요소로 분리한다.

- **Role(역할)**: 조직/계정의 정체성(템플릿)
- **Permission(기능 권한)**: 무엇을 할 수 있나? (Ability)
- **Scope(데이터 범위)**: 어느 데이터까지 가능한가?

원칙:
- **기능 접근 제어 = Permission**
- **데이터 범위 제어 = Scope + Policy/쿼리 스코핑**
- 메뉴는 role로 분기하지 않고 **required permissions로 분기**한다.

---

## 3. Spatie Permission 적용 범위(확정)

- Spatie Role/Permission은 **`admin`(세션)** 영역에만 적용한다.
- `user`(Sanctum) 영역은 별도 권한 체계로 유지한다.
- Scope는 Spatie로 해결하지 않고 도메인 규칙으로 고정해 Policy/쿼리에서 강제한다.

---

## 4. 계정 타입/역할 모델 (Role Taxonomy)

### 4.1 Account Type (누가 로그인하나)
- `guest` (비로그인)
- `user` (앱 사용자; Sanctum 토큰)
- `admin` (관리자; 세션)
    - 내부직원(Staff)
    - 병원회원(Hospital)
    - 뷰티회원(Beauty)
    - 대행사계정(Agency)

> 이 문서의 권한/메뉴는 주로 `admin` 영역(`/admin/*`, `/admin/api/*`)을 대상으로 한다.

### 4.2 Admin Role (Spatie Role 목록)
Role 네이밍(권장):
- 내부직원: `staff.super_admin`, `staff.ops`, `staff.dev`
- 병원회원: `hospital.manager`, `hospital.staff`
- 뷰티회원: `beauty.manager`, `beauty.staff`
- 대행사: `agency`

---

## 5. 도메인 정의: “고객(Lead)”의 범위(확정)

관리자에서 다루는 “고객(Lead)”은 다음으로 한정한다.

- 고객(Lead)은 **예약/상담을 통해 유입된 고객만**을 의미한다.
- 병원/뷰티가 임의로 등록한 “자체 고객 DB”는 이 범위에 포함하지 않는다. (추후 확장 시 별도 정의)

---

## 6. Scope 표(데이터 범위) (확정)

> Scope는 도메인 규칙으로 고정하여 Policy/쿼리에서 강제한다.

| Scope 코드 | 적용 대상 | 범위 정의 |
|---|---|---|
| `PUBLIC` | Guest | 공개 페이지/폼만 |
| `OWN_HOSPITAL` | 병원회원 | 자기 병원 데이터만 (1인 1병원) |
| `OWN_BEAUTY` | 뷰티회원 | 자기 뷰티(beauty) 데이터만 |
| `ASSIGNED_ACCOUNTS` | 대행사계정 | 할당된 병원/뷰티 “계정 단위” 범위만 |
| `ALL` | 내부직원 | 전체 데이터 가능(단, Permission으로 기능 제한) |

---

## 7. Permission(Ability) 네이밍 규칙(확정)

형식:
- `{domain}.{action}`

Action 후보(우선 사용):
- `read`, `create`, `update`, `delete`, `export`, `manage`

---

## 8. Permission(Ability) 표 (Spatie Permission 목록)

### 8.1 공통(Admin 공통)
| Permission | 설명 |
|---|---|
| `admin.access` | 관리자 영역 접근 |
| `dashboard.read` | 대시보드 조회 |
| `profile.read` | 내 프로필 조회 |
| `profile.update` | 내 프로필 수정 |

### 8.2 병원/뷰티 “소속 정보” 관리
| Permission | 설명 |
|---|---|
| `hospital.read` | 병원 정보 조회 |
| `hospital.update` | 병원 정보 수정 |
| `hospital.member.manage` | 병원 회원(직원) 관리 |
| `beauty.read` | 뷰티(beauty) 정보 조회 |
| `beauty.update` | 뷰티(beauty) 정보 수정 |
| `beauty.member.manage` | 뷰티 회원(직원) 관리 |

---

## 9. Role 표 (Spatie Role 목록) + 기본 Scope

| Role | 설명 | 기본 Scope |
|---|---|---|
| `staff.super_admin` | 내부직원 최고관리자 | `ALL` |
| `staff.ops` | 내부직원 관리직원(운영/CS/어드민) | `ALL` |
| `staff.dev` | 내부직원 개발자 | `ALL` |
| `hospital.manager` | 병원 지점장 | `OWN_HOSPITAL` |
| `hospital.staff` | 병원 직원 | `OWN_HOSPITAL` |
| `beauty.manager` | 뷰티 지점장 | `OWN_BEAUTY` |
| `beauty.staff` | 뷰티 직원 | `OWN_BEAUTY` |
| `agency` | 대행사계정 | `ASSIGNED_ACCOUNTS` |

---

## 10. Role × Permission 매핑표 (기본 템플릿)

표기:
- ✅ 기본 부여
- ◻️ 필요 시 부여(기본 OFF)
- ❌ 부여하지 않음

### 10.1 내부직원(Staff)
| Permission | `staff.super_admin` | `staff.ops` | `staff.dev` |
|---|---:|---:|---:|
| `admin.access` | ✅ | ✅ | ✅ |
| `dashboard.read` | ✅ | ✅ | ◻️ |
| `profile.read` | ✅ | ✅ | ✅ |
| `profile.update` | ✅ | ✅ | ✅ |
| `hospital.read` | ✅ | ✅ | ◻️ |
| `hospital.update` | ✅ | ◻️ | ❌ |
| `hospital.member.manage` | ✅ | ◻️ | ❌ |
| `beauty.read` | ✅ | ✅ | ◻️ |
| `beauty.update` | ✅ | ◻️ | ❌ |
| `beauty.member.manage` | ✅ | ◻️ | ❌ |

### 10.2 병원회원(Hospital)
| Permission | `hospital.manager` | `hospital.staff` |
|---|---:|---:|
| `admin.access` | ✅ | ✅ |
| `dashboard.read` | ✅ | ✅ |
| `profile.read` | ✅ | ✅ |
| `profile.update` | ✅ | ✅ |
| `hospital.read` | ✅ | ✅ |
| `hospital.update` | ✅ | ◻️ |
| `hospital.member.manage` | ✅ | ❌ |


### 10.3 뷰티회원(Beauty)
| Permission | `beauty.manager` | `beauty.staff` |
|---|---:|---:|
| `admin.access` | ✅ | ✅ |
| `dashboard.read` | ✅ | ✅ |
| `profile.read` | ✅ | ✅ |
| `profile.update` | ✅ | ✅ |
| `beauty.read` | ✅ | ✅ |
| `beauty.update` | ✅ | ◻️ |
| `beauty.member.manage` | ✅ | ❌ |

### 10.4 대행사(Agency)
| Permission | `agency` |
|---|---:|
| `admin.access` | ✅ |
| `dashboard.read` | ✅ |
| `profile.read` | ✅ |
| `profile.update` | ✅ |
| `lead.read` | ✅ |
| `lead.update` | ✅ |
| `lead.export` | ◻️ |
| `report.read` | ✅ |
| `report.export` | ◻️ |
| `hospital.read` | ◻️ |
| `hospital.update` | ❌ |
| `hospital.member.manage` | ❌ |

---

## 11. Admin 영역 권한 체크 규칙(구현 원칙)

### 11.1 라우트/API 접근 제어
- `/admin/*` (Inertia 페이지)와 `/admin/api/*` (JSON API)는 모두 `auth:admin`을 전제한다.
- 그 다음 단계는 **Permission 기반**으로 접근을 차단한다.
    - 예: `report.read` 없으면 `/admin/reports` 및 `/admin/api/reports` 접근 불가(403)

### 11.2 데이터 접근 제어(스코프)
- 내부직원: Permission이 있으면 접근 가능(단, 직무에 따라 Permission 자체가 제한됨)
- 병원/뷰티 회원: Permission이 있어도 **자기 소속(OWN_*) 범위만** 접근 가능
- 대행사계정: Permission이 있어도 **ASSIGNED_ACCOUNTS 범위만** 접근 가능

---

## 12. 메뉴 분기(Navigation) 규칙(구현 원칙)

- 메뉴 노출은 role로 직접 분기하지 않고 **required permissions 기반으로 노출**한다.
- 메뉴는 UX이며, 보안은 라우트/API에서 강제한다.

---

## 13. 운영 원칙(권장)

- 모든 admin 계정은 최소 1개 Role을 가진다.
- 기본은 Role 템플릿으로 부여하고, 예외는 “개별 Permission 추가/회수”로 처리한다.
- Permission 이름은 문서와 코드/데이터에서 1:1로 유지한다(오탈자 금지).

---

작성 기준: 2026-01-23
