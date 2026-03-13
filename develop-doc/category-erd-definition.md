# 카테고리 ERD 정의서

- 작성일: 2026-03-06
- 목적: 카테고리를 공통 구조로 관리하면서도 메뉴/조회 요구사항을 충족
- 기준: 테이블 최소화 + 도메인 분리 + 관리자 운영

## 1) 핵심 구조

- 물리 테이블은 2개
- `categories`: 카테고리 마스터 (도메인 + 3단계 트리)
- `category_assignments`: 실제 데이터와 카테고리 연결

## 2) 도메인 코드

- `HOSPITAL_SURGERY`
- `HOSPITAL_TREATMENT`
- `HOSPITAL_COMMUNITY`
- `BEAUTY`
- `BEAUTY_COMMUNITY`
- `FAQ`

## 3) `categories` 테이블

| 컬럼 | 타입 | 설명 |
|---|---|---|
| `id` | bigint PK | 카테고리 ID |
| `domain` | varchar(40) | 도메인 코드 |
| `parent_id` | bigint nullable FK | 상위 카테고리 ID |
| `depth` | tinyint | 1=대, 2=중, 3=소 |
| `name` | varchar(120) | 카테고리명 |
| `code` | varchar(80) nullable | 운영 코드 |
| `full_path` | varchar(255) nullable | 전체 경로 |
| `sort_order` | int | 정렬 |
| `status` | varchar(20) | `ACTIVE`/`INACTIVE` |
| `is_menu_visible` | boolean | 메뉴 노출 여부 |
| `created_at`, `updated_at` | timestamp | 생성/수정 시각 |

권장 제약:

- unique(`domain`, `parent_id`, `name`)
- unique(`domain`, `code`)
- check(`depth` in (1,2,3))

## 4) `category_assignments` 테이블

| 컬럼 | 타입 | 설명 |
|---|---|---|
| `id` | bigint PK | 연결 ID |
| `categorizable_type` | varchar(150) | 대상 모델 타입 |
| `categorizable_id` | bigint | 대상 모델 ID |
| `category_id` | bigint FK | 카테고리 ID |
| `is_primary` | boolean | 대표 카테고리 여부 |
| `created_at`, `updated_at` | timestamp | 생성/수정 시각 |

권장 제약:

- unique(`categorizable_type`, `categorizable_id`, `category_id`)
- index(`categorizable_type`, `categorizable_id`)

## 5) 메뉴/조회 규칙

### 5.1 메뉴 숨김

- 중/소분류 숨김은 `is_menu_visible=false`
- 숨겨도 기존 매핑 데이터는 유지

### 5.2 대분류 클릭 조회

1. 선택한 대분류 ID 기준
2. 하위 중/소분류 ID까지 수집
3. 수집된 ID 전체로 `category_assignments` 조인 조회

결과: 대분류만 클릭해도 하위 데이터가 즉시 노출됨

## 6) 운영 정책

- 카테고리 생성/수정/삭제: 최고관리자 권한
- 삭제 대신 기본은 비활성(`status=INACTIVE`) 운영 권장
- 중복 의미 카테고리 신규 생성 금지, 기존 재사용

## 7) 구현 상태 메모

- Staff 카테고리 API는 `domain` 요청값 필수
  - 예: `/api/v1/staff/categories?domain=FAQ`
