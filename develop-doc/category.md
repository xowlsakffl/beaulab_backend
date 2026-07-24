# 카테고리 설계

- 작성 기준: 2026-07-24
- 기준 코드: `categories`, `category_assignments`, `category_usages`, `CategoryDefinitions`, `CategoryFactory`, `CategorySeeder`

## 1. 설계 원칙

카테고리는 두 가지 책임으로 분리한다.

| 구분 | 테이블/파일 | 책임 |
|---|---|---|
| 카테고리 트리 | `categories`, `app/Domains/Common/Category/Definitions/data/trees/*` | 서비스 분류 체계 원본 |
| 카테고리 그룹 | `categories.group_code` | 같은 도메인 안에서 성형/쁘띠 같은 카테고리 성격 구분 |
| 사용처 노출 목록 | `category_usages`, `app/Domains/Common/Category/Definitions/data/usages/*` | 특정 화면/기능에서 보여줄 카테고리 선별 |

`categories`에는 화면별 노출 플래그를 추가하지 않는다. 병원 진료과목, 의료진 진료분야, 앱 필터처럼 같은 카테고리 트리에서 서로 다른 depth의 노드를 골라 써야 하는 기능은 `category_usages`로 관리한다.

`group_code`는 화면별 사용처가 아니라 카테고리 자체의 성격값이다. 병원 의료 카테고리에서는 같은 `HOSPITAL_MEDICAL` 트리 안에서 성형/쁘띠를 구분하기 위해 사용한다.

## 2. 병원 의료 카테고리

병원/의료진/후기/영상에서 쓰는 의료 카테고리는 공통 도메인 하나를 사용한다.

```php
Category::DOMAIN_HOSPITAL_MEDICAL
```

트리는 `app/Domains/Common/Category/Definitions/data/trees/hospital_medical.php`에 정의한다.

최상위 구조:

```text
HOSPITAL_MEDICAL
- 눈
- 코
- 지방흡입 / 이식
- 가슴
- 거상
- 안면윤곽 / 양악
- 모발이식
- 기타
- 리프팅
- 필러
- 보톡스
- 지방분해주사
- 피부
- 제모/탈모
- 치과
- 부인과
- 안과
- 한방
```

`성형`, `쁘띠`는 카테고리 노드로 저장하지 않는다. 이 둘은 `categories.group_code`로만 구분한다.

| group_code | 표시명 | 의미 |
|---|---|---|
| `SURGERY` | 성형 | 눈, 코, 지방흡입 / 이식, 가슴, 거상, 안면윤곽 / 양악, 모발이식, 기타 |
| `TREATMENT` | 쁘띠 | 리프팅, 필러, 보톡스, 지방분해주사, 피부, 제모/탈모, 치과, 부인과, 안과, 한방 |

시더에서는 최상위 카테고리에 `group_code`를 정의하고, 하위 카테고리는 부모의 `group_code`를 상속한다. 운영 화면에서 하위 카테고리를 생성할 때도 부모와 다른 `group_code`는 허용하지 않는다.

## 3. 병원/의료진 진료과목

병원 진료과목과 의료진 진료분야 셀렉트는 전체 트리를 직접 보여주지 않는다. `category_usages`의 사용처 목록만 조회한다.

```php
CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT
```

정의 파일:

```text
app/Domains/Common/Category/Definitions/data/usages/hospital_doctor_subject.php
```

이 usage에는 성형 쪽 대분류와 쁘띠 쪽 대분류가 같이 들어간다. 이건 depth 기준이 아니라 “병원/의료진이 진료과목으로 표방할 수 있는 노드” 기준이다.
성형/쁘띠 구분 표시는 `category_usages`가 아니라 각 카테고리의 `group_code`를 기준으로 한다.

주의할 점:

- `성형`, `쁘띠` 자체는 `categories` 트리에도, 진료과목 usage에도 넣지 않는다.
- 진료과목에 노출할 항목은 `app/Domains/Common/Category/Definitions/data/usages/hospital_doctor_subject.php`에 명시된 code만 기준으로 한다.
- 시더 재실행 시 usage 파일에 없는 기존 `category_usages` row는 삭제한다. 기존 DB에 잘못 들어간 root usage가 남아 있으면 시더를 다시 실행해 정리한다.

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

## 5. 정의/동기화 구조

```text
app/Domains/Common/Category/
└── Definitions/
    ├── CategoryDefinitions.php
    └── data/
        ├── trees/
        │   ├── hospital_medical.php
        │   ├── hospital_evaluation.php
        │   ├── talk.php
        │   ├── beauty.php
        │   └── faq.php
        └── usages/
            ├── hospital_doctor_subject.php
            ├── hospital_review_surgery.php
            ├── hospital_review_treatment.php
            ├── hospital_event_surgery.php
            ├── hospital_event_treatment.php
            ├── hospital_video_category.php
            ├── hospital_event_ad_surgery.php
            └── hospital_event_ad_treatment.php
```

역할:

- `CategoryDefinitions`: 카테고리 트리와 usage 정의 파일의 app 기준 진입점
- `CategoryFactory`: `CategoryDefinitions`에서 정의를 읽어 `categories`, `category_usages`에 반영
- `CategorySeeder`: `CategoryFactory::seed...()` 메서드를 호출하는 얇은 Seeder

동기화 순서:

1. `trees/*` 기준으로 `categories` 생성
2. usage 파일의 `code`를 `categories.code`와 매칭
3. `category_usages`에 사용처별 노출 목록 생성

usage 파일은 DB id를 직접 쓰지 않고 `code`로 참조한다. 동기화 전에는 id가 확정되어 있지 않기 때문이다.

기준 데이터는 Seeder/Factory 안에 직접 쓰지 않는다. 카테고리 정의를 변경할 때는 `app/Domains/Common/Category/Definitions/data`를 수정하고, DB 반영 방식이 바뀔 때만 `CategoryFactory`의 seed 처리 로직을 수정한다.

## 6. API 조회 기준

공통 카테고리 selector는 다음 파라미터를 지원한다.

| 파라미터 | 의미 |
|---|---|
| `domain` | 카테고리 원본 도메인 |
| `usage` | 사용처별 노출 목록 필터 |
| `group_code` | 성형/쁘띠 그룹 필터(`SURGERY`, `TREATMENT`) |
| `parent_id` | 특정 부모 id의 자식 조회 |
| `parent_code` | 특정 부모 code의 자식 조회 |

예시:

```text
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&usage=HOSPITAL_DOCTOR_SUBJECT
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&usage=HOSPITAL_DOCTOR_SUBJECT&group_code=SURGERY
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&usage=HOSPITAL_REVIEW_SURGERY
GET /api/v1/staff/categories/selector?domain=HOSPITAL_MEDICAL&parent_id=1
```

selector/list 응답은 `group_code`, `group_label`을 내려준다. 프론트는 `group_code`로 필터/섹션 구분을 처리하고, `group_label`은 표시용으로만 사용한다.

## 7. 금지 기준

- 병원/의료진 진료과목을 별도 `HOSPITAL_DOCTOR` 도메인으로 복제하지 않는다.
- `성형`, `쁘띠/피부`를 진료과목 카테고리 노드로 저장하지 않는다.
- 성형/쁘띠 판별을 `category_usages`로 역추론하지 않는다. 판별 기준은 `categories.group_code`다.
- 화면별 노출 여부를 `categories` 컬럼으로 계속 늘리지 않는다.
- 후기 게시판 구분값과 카테고리 원본 도메인을 같은 의미로 쓰지 않는다.
- depth 숫자만으로 소분류를 판단하지 않는다. 트리 구조는 바뀔 수 있으므로 leaf 여부를 기준으로 한다.
