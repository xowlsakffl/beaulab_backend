# 카테고리 설계

- 작성일: 2026-06-10
- 기준 코드: `categories`, `category_assignments`, `category_usages`, `CategoryFactory`

## 1. 설계 원칙

카테고리는 두 가지 책임으로 분리한다.

| 구분 | 테이블/파일 | 책임 |
|---|---|---|
| 카테고리 트리 | `categories`, `database/seeders/data/categories/trees/*` | 서비스 분류 체계 원본 |
| 사용처 노출 목록 | `category_usages`, `database/seeders/data/categories/usages/*` | 특정 화면/기능에서 보여줄 카테고리 선별 |

`categories`에는 화면별 노출 플래그를 추가하지 않는다. 병원 진료과목, 의료진 진료분야, 앱 필터처럼 같은 카테고리 트리에서 서로 다른 depth의 노드를 골라 써야 하는 기능은 `category_usages`로 관리한다.

## 2. 병원 의료 카테고리

병원/의료진/후기/영상에서 쓰는 의료 카테고리는 공통 도메인 하나를 사용한다.

```php
Category::DOMAIN_HOSPITAL_MEDICAL
```

트리는 `database/seeders/data/categories/trees/hospital_medical.php`에 정의한다.

최상위 구조:

```text
HOSPITAL_MEDICAL
- 눈
- 코
- 지방흡입/이식
- 안면윤곽/양악
- 가슴
- 남자성형
- 기타성형
- 안티에이징
- 얼굴
  - 리프팅
  - 보톡스
  - 필러
- 체형
- 피부
- 치과
- 두피
- 탈모
- 제모
- 안과
- 한방
- 기타쁘띠/피부
```

`성형`, `쁘띠/피부`는 카테고리 노드로 저장하지 않는다. 이 둘은 후기/영상 게시판을 나누기 위한 사용처 그룹명이지, 사용자가 선택하는 진료과목이 아니다.

## 3. 병원/의료진 진료과목

병원 진료과목과 의료진 진료분야 셀렉트는 전체 트리를 직접 보여주지 않는다. `category_usages`의 사용처 목록만 조회한다.

```php
CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT
```

시더 파일:

```text
database/seeders/data/categories/usages/hospital_doctor_subject.php
```

이 usage에는 성형 쪽 대분류와 쁘띠/피부 쪽 일부 중분류가 섞여 들어갈 수 있다. 이건 depth 기준이 아니라 “병원/의료진이 진료과목으로 표방할 수 있는 노드” 기준이다.

주의할 점:

- `성형`, `쁘띠/피부` 자체는 `categories` 트리에도, 진료과목 usage에도 넣지 않는다.
- 진료과목에 노출할 항목은 `database/seeders/data/categories/usages/hospital_doctor_subject.php`에 명시된 code만 기준으로 한다.
- 시더 재실행 시 usage 파일에 없는 기존 `category_usages` row는 `INACTIVE`로 변경한다. 기존 DB에 잘못 들어간 root usage가 남아 있으면 시더를 다시 실행해 정리한다.

## 4. 후기 게시판 구분

`HospitalReview.category_domain`은 더 이상 `categories.domain`이 아니다. 이 값은 성형후기/시술후기 게시판 구분값이다.

| 게시판 | 저장값 | usage |
|---|---|---|
| 성형후기 | `HOSPITAL_REVIEW_SURGERY` | `CategoryUsage::USAGE_HOSPITAL_REVIEW_SURGERY` |
| 시술후기 | `HOSPITAL_REVIEW_TREATMENT` | `CategoryUsage::USAGE_HOSPITAL_REVIEW_TREATMENT` |

후기 작성 시 선택된 leaf 카테고리가 어떤 후기 usage root 아래에 속하는지 보고 `category_domain`을 계산한다. 따라서 후기 카테고리 검증 기준은 다음과 같다.

- 카테고리 domain은 `HOSPITAL_MEDICAL`
- 선택 카테고리는 자식이 없는 leaf여야 함
- 선택 카테고리들은 같은 후기 usage 그룹에 속해야 함
- `hospital_review_surgery.php` usage 아래면 성형후기, `hospital_review_treatment.php` usage 아래면 시술후기로 저장

## 5. 시더 구조

```text
database/seeders/data/categories/
├── trees/
│   ├── hospital_medical.php
│   ├── hospital_evaluation.php
│   ├── talk.php
│   ├── beauty.php
│   └── faq.php
└── usages/
    ├── hospital_doctor_subject.php
    ├── hospital_review_surgery.php
    └── hospital_review_treatment.php
```

시더 실행 순서:

1. `trees/*` 기준으로 `categories` 생성
2. usage 파일의 `code`를 `categories.code`와 매칭
3. `category_usages`에 사용처별 노출 목록 생성

usage 파일은 DB id를 직접 쓰지 않고 `code`로 참조한다. 시더 실행 전에는 id가 확정되어 있지 않기 때문이다.

## 6. API 조회 기준

공통 카테고리 selector는 다음 파라미터를 지원한다.

| 파라미터 | 의미 |
|---|---|
| `domain` | 카테고리 원본 도메인 |
| `usage` | 사용처별 노출 목록 필터 |
| `parent_id` | 특정 부모 id의 자식 조회 |
| `parent_code` | 특정 부모 code의 자식 조회 |

예시:

```text
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&usage=HOSPITAL_DOCTOR_SUBJECT
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&usage=HOSPITAL_REVIEW_SURGERY
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&parent_id=1
```

## 7. 금지 기준

- 병원/의료진 진료과목을 별도 `HOSPITAL_DOCTER` 도메인으로 복제하지 않는다.
- `성형`, `쁘띠/피부`를 진료과목 카테고리 노드로 저장하지 않는다.
- 화면별 노출 여부를 `categories` 컬럼으로 계속 늘리지 않는다.
- 후기 게시판 구분값과 카테고리 원본 도메인을 같은 의미로 쓰지 않는다.
- depth 숫자만으로 소분류를 판단하지 않는다. 트리 구조는 바뀔 수 있으므로 leaf 여부를 기준으로 한다.
