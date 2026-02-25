# Spatie Permission 동작 원리 및 현재 프로젝트 적용 방식

## 핵심 요약
- `model_has_permissions` 는 **"사용자에게 권한을 직접 부여"** 했을 때만 채워진다.
- 현재 `AccountStaffSeeder` 는 `syncRoles(['beaulab.super_admin'])` 만 호출하므로,
  `model_has_roles` 만 채워지고 `model_has_permissions` 는 비어있는 것이 정상이다.
- 실제 권한 체크(`can`)는 **직접 권한 + 역할을 통해 상속된 권한**을 합쳐서 판단한다.

## 테이블별 역할

### 1) `permissions`
- 시스템이 사용할 권한(ability) 사전.
- 예: `beaulab.doctor.create`, `beaulab.expert.update`.

### 2) `roles`
- 권한 묶음(역할) 정의.
- 예: `beaulab.super_admin`, `beaulab.admin`.

### 3) `role_has_permissions`
- 역할에 어떤 권한이 들어있는지 매핑.
- 예: `beaulab.super_admin` -> staff guard의 전체 permission.

### 4) `model_has_roles`
- 어떤 모델(예: `AccountStaff`)에 어떤 역할을 부여했는지 매핑.
- `syncRoles()` / `assignRole()` 호출 시 이 테이블이 변경된다.

### 5) `model_has_permissions`
- 모델에 권한을 **직접** 부여했을 때만 매핑.
- `givePermissionTo()` / `syncPermissions()` 를 모델에 호출했을 때 채워진다.

## 현재 프로젝트 시딩 흐름
1. `AuthorizationSeeder`
   - `AccessPermissions::byGuard()` 기준으로 `permissions` 생성.
   - `AccessRoles::roleNamesByGuard()` 기준으로 `roles` 생성.
   - `AccessRoles::mapByGuard()` 기준으로 `role_has_permissions` 동기화.
2. `AccountStaffSeeder`
   - 스태프 계정을 만들거나 가져온 뒤,
   - `syncRoles(['beaulab.super_admin'])` 실행.
   - 따라서 `model_has_roles` 만 기록되고, `model_has_permissions` 는 기록되지 않음.

## Policy/Gate 체크 기준
- Staff Policy 구현은 대부분 `$actor->can('permission.name')` 를 사용한다.
- `can()` 은 Spatie의 `HasRoles`를 통해
  - 직접 권한(`model_has_permissions`)과
  - 역할 상속 권한(`model_has_roles` + `role_has_permissions`)
  둘 다 합쳐서 검사한다.
- 그래서 `model_has_permissions` 가 비어 있어도 role 경유 권한으로 통과할 수 있다.

## 언제 `model_has_permissions` 를 써야 하나?
- 권장 기본: Role 중심 운영 (`model_has_roles` + `role_has_permissions`).
- 예외적으로 사용자별 override가 필요할 때만 직접 권한 부여 사용.

## 점검 SQL 예시
```sql
-- 특정 staff가 가진 role
SELECT r.name
FROM model_has_roles mhr
JOIN roles r ON r.id = mhr.role_id
WHERE mhr.model_type = 'App\\Domains\\Staff\\Models\\AccountStaff'
  AND mhr.model_id = :staff_id;

-- role을 통해 상속된 permission
SELECT DISTINCT p.name
FROM model_has_roles mhr
JOIN role_has_permissions rhp ON rhp.role_id = mhr.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE mhr.model_type = 'App\\Domains\\Staff\\Models\\AccountStaff'
  AND mhr.model_id = :staff_id;

-- 직접 부여된 permission
SELECT p.name
FROM model_has_permissions mhp
JOIN permissions p ON p.id = mhp.permission_id
WHERE mhp.model_type = 'App\\Domains\\Staff\\Models\\AccountStaff'
  AND mhp.model_id = :staff_id;
```

## 결론
- 질문한 현상(최고관리자 시더 후 `model_has_permissions` 비어있음)은 **정상 동작**이다.
- 현재 코드베이스는 Role 기반 권한 운영이며,
  Policy는 `$actor->can(...)` 으로 "직접+역할 상속"을 함께 평가한다.
