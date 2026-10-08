# API·인증

프론트엔드 연동과 새 API 개발의 공통 계약입니다. 개별 필드·선택값·권한 이름의 최종 기준은 각 Request, DTO, Policy입니다.

## 직원 알림

기본 경로는 `/api/v1/staff/notifications`입니다. 직원 웹 세션·CSRF·`common.access`를 사용하며 항목별 현재 업무 권한으로 수신함을 제한합니다. 다른 직원의 ID를 입력받는 API는 없습니다.

| 메서드·하위 경로 | 입력 / 응답 data |
| --- | --- |
| GET 기본 경로 | `unread_only`, `event_type`, `start_date/end_date`, `cursor`, `limit`(기본 20, 최대 50). `{items,next_cursor,unread_count,read_all_token,types}` |
| GET `/unread-count` | `{unread_count,read_all_token}`. 개수는 미읽음 행 수이며 메시지 집계 수 합계가 아님 |
| PATCH `/{id}/read` | `expected_event_count`, 선택적 `latest_message_id`. `{notification,changed,unread_count}` |
| POST `/read-all` | 조회 응답의 `read_all_token`. `{updated_count,unread_count}` |
| GET `/preferences` | `{preferences:[{event_type,title,target_type,in_app}]}` |
| PUT `/preferences` | `{preferences:[{event_type,in_app}]}`. 자기 수신 설정만 저장 |

날짜는 한국 시간 기준 날짜 경계를 사용합니다. 커서와 전체 읽음 토큰은 직원·권한·필터에 묶인 불투명 값으로 만료되거나 조건이 달라지면 422입니다. 클라이언트는 토큰을 해석하지 않고 목록을 다시 조회합니다. 개별 읽음 버전이 달라졌으면 `changed:false`이며 새로운 미읽음을 보존합니다.

항목의 `target_route`는 기존 직원 화면의 상대 경로입니다. 삭제된 대상은 `target_available:false`, `target_route:null`을 반환합니다. 원본의 최신 상태와 `target_actionable`은 읽음과 독립적입니다. 개인정보 본문을 알림에 복제하지 않으며 메타데이터는 병의원·대상 이름과 식별자로 제한합니다. 저장·중복 방지·실시간·배포 순서는 [메시징](messaging.md#직원-업무-알림)을 따릅니다.

## 금지어 설정

- 직원 `/api/v1/staff/harmful-words` GET은 `q`, `scopes[]`, `statuses[]`, `page`를 받는 20개 단위 목록이다. `beaulab.harmful_word.show` 또는 `manage` 권한으로 조회하며 변경은 `manage`만 허용한다. 역할 우회는 없다.
- POST `/validate`는 줄바꿈·쉼표로 구분한 최대 200개 단어의 신규·중복·오류를 분석한다. POST 기본 경로는 `text`, `scopes`, `matching_mode`, `detect_variants`, `status`, `exceptions`를 받으며 다시 검증한다. 정규화 후 중복은 제외하고 미사용 단어를 자동 활성화하지 않는다. 입력 오류가 있으면 전체 등록을 거절한다.
- PUT은 `operation=edit|enable|disable|delete`, `items=[{id,lock_version}]`를 받는다. edit은 한 개이며 `word`와 위 설정값을 모두 지정한다. 일괄 변경은 최대 50개, 버전 확인·행 잠금·운영 이력을 같은 트랜잭션에 포함한다. 기존 조회 화면은 현재 페이지의 선택만 전송한다.
- POST `/check`는 조회 권한으로 `scope/text`를 검사하는 읽기 전용 API다. `allowed`, `active_rule_count`, 최대 20개의 `matches`(rule_id, word, expression)를 반환한다. 표현은 정규화된 검사 사본이므로 원문의 위치 인덱스가 아니다. 분당 60회로 제한하며 검사 문장을 이력·로그에 저장하지 않는다.
- 영역은 `REVIEW/TALK/EVALUATION/COMMENT/NICKNAME`이다. 공통 검사기는 후기·토크 제목/본문, 토크 투표 항목, 후기·토크 댓글/답글, 변경된 프로필 닉네임과 자동 생성 조합에 연결했다. 현재 평가 작성 API, 일반회원 게시물 수정 API, 일반회원 가입 API가 없어 해당 연결은 향후 구현 시 필요하다. 평가 사전 설정이 존재해도 작성 기능 구현을 의미하지 않는다.
- 차단은 저장·파일 첨부 전에 `INVALID_REQUEST` 422와 `details.errors`의 필드별 오류로 응답한다. 예: `title`, `content`, `poll.options.0`, `nickname`. 원문을 치환하거나 기존 게시물·회원 닉네임을 소급 수정하지 않는다. 새 소비자는 실패 시 초안·첨부 선택을 유지하고 해당 필드 오류를 표시해야 한다.
- `2026_10_08_120000_create_harmful_words_tables.php`는 금지어·영역·예외 테이블을 새로 만든다. 초기 사전은 비워 두며 Seeder를 자동 실행하지 않는다. 배포 전 테이블과 권한을 준비해야 하며 사전 조회 실패를 정상 통과로 취급하지 않는다. 실제 단어와 예외의 적절성은 운영자가 문장 검사로 확인한다.

## 자동 닉네임

- 직원 `/api/v1/staff/nickname-words`의 GET은 `type=ADJECTIVE|NOUN`, `q`, `status=ACTIVE|INACTIVE`, `page`를 받으며 20개 단위 목록·전체/사용 개수를 반환한다. 조회는 `beaulab.nickname.show` 또는 `beaulab.nickname.manage`, 모든 변경은 `beaulab.nickname.manage`가 필요하다. 역할에 의한 우회는 없다.
- POST `/validate`는 `type/text`를 읽기 전용으로 분석한다. 공백·줄바꿈·쉼표로 구분해 최대 200개를 처리하며 한글 완성형 1~20자만 허용한다. 입력 내 중복과 기존 단어는 제외 대상으로 반환한다. POST 기본 경로는 같은 입력을 다시 검증하고, 잘못된 단어가 하나라도 있으면 전체 등록을 거절한다. 미사용 단어도 중복으로 취급하며 자동 활성화하지 않는다.
- PUT은 `type`, `operation=edit|enable|disable|delete`, `items=[{id,lock_version}]`를 받는다. edit은 한 개만 지정하고 `word`가 필요하다. 같은 종류의 행 잠금과 버전 검사로 동시 덮어쓰기 및 마지막 활성 단어 제거를 차단하며, 변경·운영 이력을 한 트랜잭션으로 저장한다. 종류 변경은 지원하지 않는다.
- GET `/preview`는 실제 생성 규칙에 따른 최대 5개 예시이며 닉네임을 예약하지 않는다. 생성은 활성 형용사+명사, 기본 숫자 없음이다. 기존 회원(소프트 삭제 포함)과 중복된 조합에만 1~999999 숫자를 붙인다. 최대 길이는 46자이며 기존 프로필의 50자 제한 안이다. 단어 변경은 기존 회원을 수정하지 않는다. 생성 단어 풀의 영속 캐시는 두지 않아 다음 요청부터 현재 설정을 읽는다.
- `AccountUserCreateWithNicknameAction::execute($validatedAttributes)`는 가입 자격·필수값을 검증한 내부 가입 흐름에서 호출할 진입점이다. 최종 회원 INSERT의 닉네임 고유키 충돌만 제한적으로 재시도하며 다른 고유키 오류는 보존한다. 현재 일반회원 가입·소셜가입 API는 없으므로 해당 API가 생길 때 이 Action을 연결해야 한다. 인증 없는 가입 API를 추가하지 않았다. 금지어 검사는 `NICKNAME` 영역의 활성 규칙으로 완성된 조합과 숫자 접미사 결과를 모두 검사한다. 금지어에 걸린 기본 조합을 숫자로 우회하지 않으며, 최대 30회 시도 후 실패하면 설정 확인 오류를 반환한다.
- 신규 테이블은 `2026_10_07_200000_create_nickname_words_table.php`로 생성한다. `NicknameWordSeeder`는 해당 종류가 완전히 비어 있을 때만 초기 형용사·명사 각각 20개를 넣고, 기존 단어·사용 여부를 덮어쓰거나 삭제한 단어를 재추가하지 않는다. 기존 DB 적용은 승인된 대상에서 이 마이그레이션과 이 Seeder만 지정한다. 신규 권한은 기존 AuthorizationSeeder 정의에 포함되며, 전환된 기존 직원에게는 승인된 권한만 별도 부여한다.

## 카테고리 관리

- `GET /api/v1/staff/categories/management?domain=...`는 해당 도메인의 전체 트리, 연결 콘텐츠 수, 사용처별 직접 설정과 상위 상태를 반영한 유효 설정, 수정 버전을 반환한다.
- `POST /api/v1/staff/categories/management`는 조회한 `version`, `domain`, `operation=create|update|delete|reorder|usage|label`을 받는다. 등록·이름 수정·삭제·순서는 `beaulab.category.manage`, 사용처 설정은 `beaulab.category.status_update`가 필요하다. 공통 상태 변경은 기존 수정 Policy에 따라 두 권한이 모두 필요하다. 화면 조회는 둘 중 하나로 가능하다.
- 이름·코드·부모·그룹은 `categories`, 업무별 표시명(`display_name`)·사용·메뉴 노출·순서는 `category_usages`, 기존 콘텐츠 연결은 `category_assignments`를 유지한다. 의료 분류는 성형·쁘띠·기획전을 구분하며 최대 3단계다. 토크·뷰티·FAQ·병의원 평가·분과와 챗봇 Q&A는 의료 분류와 섞지 않는다.
- `label`은 `usage/id/display_name`으로 해당 업무의 이름만 바꾼다. 빈 값은 공통 이름을 따른다. selector와 콘텐츠 응답은 업무별 이름·경로를 반환하지만 DB의 공통 이름·ID·경로와 가격 판정은 바꾸지 않는다. 의료 분류의 공통 수정·삭제는 `confirm_global=true`가 필요하다.
- 병의원 진료과목(`HOSPITAL_SUBJECT`)과 의료진 진료분야(`DOCTOR_SUBJECT`)는 별도 설정이다. 기존 `HOSPITAL_DOCTOR_SUBJECT`를 복사해 시작하며, 구형 계약은 유지하지만 새 화면은 이를 변경하지 않는다. 분과는 `HOSPITAL_DEPARTMENT` 단층 트리이며 `categories.code`와 기존 `hospitals.department` 코드로 연결한다. 코드 변경 및 사용 중인 분과 삭제는 금지하고, 비활성 분과는 새 선택에서 제외하되 기존 병의원의 값을 유지한다.
- 기존 사용처 행이 없는 하위 분류는 부모의 사용 설정을 상속한다. 신규 분류는 선택한 사용처만 사용하도록 명시적으로 저장한다. 부모 중지·숨김은 하위의 유효 상태에 반영하되 하위의 직접 설정을 덮어쓰지 않는다.
- 메뉴 노출은 사용 여부와 별개이며 콘텐츠 연결·공개 상태를 변경하지 않는다. selector는 `usage`를 모든 단계에 전달하고, `is_menu_visible=true`를 지정한 조회만 메뉴에서 숨긴 항목을 제외한다. 사용자 앱의 메뉴 UI 연결은 별도 소비자가 이 계약을 사용해야 한다.
- 순서는 같은 도메인·그룹·부모의 전체 형제 ID로만 변경한다. 부모 이동·병합은 제공하지 않는다. 삭제 시 하위 분류, 다형 연결, 비대면 상담, 노출설정, 상담 단가 참조를 검사한다. 이름·상태·사용처·순서 변경은 기존 운영 이력에 기록한다.
- 광고 예약 잠금과 분류 잠금, 버전 비교로 관리 화면의 동시 덮어쓰기를 차단한다. 예약·진행 중인 승인 유료 광고가 연결된 분류는 중지·숨김을 거절한다. 광고 승인 및 승인 광고 수정도 잠금 안에서 현재 분류의 사용·노출을 재확인한다.
- 기존 DB에는 `category_usages.is_menu_visible` (`boolean NOT NULL DEFAULT true`)와 `display_name` (`varchar(120) NULL`)이 필요하다. 생성 마이그레이션에 반영했으며, 기존 DB는 승인된 대상에서 `php artisan category:prepare-management --apply`로 준비한다. 먼저 `storage/app/private/category-management`에 연결 데이터까지 JSON 백업하고 누락 컬럼·분과 7개·병의원/의료진 사용처만 추가한다. 재실행은 기존 설정을 덮어쓰지 않는다. MySQL DDL은 트랜잭션 롤백 대상이 아니므로 실패 시 남은 스키마를 확인하고 재실행한다. `migrate:fresh`, 전체 재시딩·초기화는 하지 않는다.

## 상담신청 단가

상담신청 단가 설정은 별도 `/api/v1/staff/consultation-pricing`의 GET/PUT/DELETE로 제공한다. `beaulab.db_pricing.manage` 권한이 필요하며 PUT은 즉시/예약 저장, DELETE는 `pending_id`와 `lock_version`이 일치하는 미래 예약 취소다. GET `/consultation-pricing/preview`는 이벤트 조회·등록·수정 중 하나의 권한으로 대표 카테고리와 가격의 현재 단가를 확인한다. 단가 계약과 초기화는 [충전금](hospital-wallet.md#상담신청-단가)을 따른다.

병의원 답변 저장은 PUT `/api/v1/hospital/remote-consultation-quotes/{quote}/answer`다. 활성 병의원 계정의 소유 견적이며 관리자 승인이 완료된 신청에만 허용한다. 입력은 `answer`, `total_amount`이며 단가·과금 여부는 서버가 결정한다. 실제 소진은 최초 답변 시에만 발생한다.

## 광고비용 설정

- `/api/v1/staff/ad-pricing` GET/PUT/DELETE는 `beaulab.ad_pricing.manage` 권한으로 보호한다. 역할명에 의한 우회는 없다.
- `group`은 기존 광고 그룹 `main/surgery/petit/etc`다. GET은 현재 가격표, 서버 시각, `lock_version`, `current_version_id`, 미래 예약을 반환한다.
- PUT은 그룹 내 모든 위치의 `prices` 맵, `lock_version`, `current_version_id`(최초 0), 선택적인 `effective_at`(시간대 포함)을 받는다. 미지정 시 즉시 적용한다. DELETE는 같은 버전 정보와 `pending_id`로 아직 적용되지 않은 예약만 취소한다.
- 위치별 상세 설정 API는 아직 제공하지 않는다. 가격 단위·기존 광고 보존 정책은 [광고 기본 비용](hospital-wallet.md#광고-기본-비용)을 따른다.

## 직원 내 프로필

- `GET /api/v1/staff/profile`은 `common.profile.show`로 본인 정보와 현재 권한을 조회합니다. 기존 필드에 `phone`, `department`, `job_title`, `version`을 포함합니다. 다른 직원 ID는 입력받지 않습니다.
- `PATCH|PUT /api/v1/staff/profile`은 `common.profile.update`로 이름·전화번호만 수정합니다. 조회 응답의 `version`은 필수이고 `name/phone`은 변경한 필드만 전달합니다. 전화번호의 공백·괄호·하이픈을 제거한 뒤 직원 등록과 같은 형식을 검사합니다. 직원 ID·이메일·부서·직급·역할·권한·상태 변경은 직원 관리 API를 사용합니다.
- 저장 시 계정 행을 잠그고 현재 계정 상태·권한·`lock_version`을 재검사합니다. 변경이 있으면 버전을 증가시키며 오래된 요청은 `details.errors.version` 422로 거절합니다. 응답은 `{profile}`입니다. 기존 프로필 수정 소비자는 필수 버전과 수정 허용 필드 변경에 맞춰 함께 배포해야 합니다. 스키마 변경은 없습니다.
- `PATCH|PUT /api/v1/staff/password`은 같은 수정 권한으로 `current_password`, `password`, `password_confirmation`을 받습니다. 새 비밀번호는 직원 등록과 같은 최소 12자·문자·숫자·최대 72자 조건이며 현재 비밀번호와 달라야 합니다. 현재 비밀번호 확인·저장·기존 Sanctum 토큰 삭제를 한 트랜잭션에서 처리하고 수정 버전을 증가시킵니다. 현재 비밀번호 불일치는 `details.errors.current_password`로 반환합니다.
- 변경 후 기존 웹 세션은 비밀번호 지문 불일치로 다음 인증 요청에서 거절됩니다. 클라이언트는 메모리 세션을 지우고 다른 탭에 인증 변경을 알린 뒤 로그인으로 이동합니다. 실제 비밀번호는 로그·응답에 포함하지 않습니다.

## 직원 관리

- 기본 경로는 `/api/v1/staff/staffs`입니다. GET 목록, POST 등록, GET/PUT/DELETE `/{staff}`, GET `/options`, PUT `/role-template`, GET `/{staff}/operation-histories`를 제공합니다.
- 조회·등록·수정·삭제는 기존 `beaulab.staff.*` 권한으로 검사합니다. 역할·개인 권한·역할 템플릿 변경은 추가로 `beaulab.staff.permissions_manage`가 필요하며 자신에게 없는 권한을 부여할 수 없습니다.
- 역할은 총괄(`beaulab.super_admin`), 매니저(`beaulab.admin`), 사원(`beaulab.staff`), 개발자(`beaulab.dev`)입니다. 등록과 역할 변경 시에만 해당 역할 기본권한을 개인 권한으로 복사합니다. 같은 역할의 개인 권한 변경은 그대로 저장하며, 역할 템플릿 변경은 기존 직원에게 소급하지 않습니다.
- 전환 완료 직원(`permissions_initialized_at` 존재)의 `can()`과 인증 응답은 개인 권한만 사용합니다. 총괄·개발자도 우회하지 않습니다. 내부 도구 접근은 `beaulab.internal_tool.show`와 기존 도구 허용 설정을 함께 검사합니다.
- 수정은 응답의 `version`과 `updated_at`을 보내야 합니다. 템플릿 수정은 해당 템플릿 `version` 해시가 필요합니다. 오래된 요청은 422로 거절합니다.
- 다른 직원의 비밀번호·이메일 변경과 재활성화는 역할·권한 관리 권한 및 대상의 기존 권한을 모두 보유해야 합니다. 본인 삭제·차단 및 마지막 권한 관리 가능 직원의 접근 제거는 차단합니다. 삭제는 소프트 삭제이며 운영 이력을 남깁니다.
- 부서·직급은 기존 `department/job_title`, 전화번호는 `phone`입니다. 등록에는 역할만 선택하고 권한표는 수정 화면에서 편집합니다. 관리자 메모·히스토리 조회는 대상 도메인의 기존 조회 권한을 유지합니다.

## 경로와 응답

기본 경로는 `/api/v1/{staff|hospital|beauty|user}`입니다.
[공통 라우트](../routes/api.php)가 [Actor별 라우트](../app/Modules)를 연결합니다. API 문서는 Scramble의 `/docs/api`, 스키마는 `/docs/api.json`이며 [내부 도구 접근 제한](operations.md)이 적용됩니다.

일반 JSON API는 [ApiResponse](../app/Common/Http/Responses/ApiResponse.php)를 사용합니다.

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 0,
    "last_page": 1
  },
  "traceId": "request-id"
}
```

목록은 [PaginatedResponse](../app/Common/Support/PaginatedResponse.php)로 항목과 meta를 만들고 Controller에서 공통 응답으로 감쌉니다.
단건·명령 응답의 data 형태는 각 DTO/Controller를 따릅니다. 의미 없는 전체 모델을 반환하지 않습니다.

```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "입력값을 확인해 주세요.",
    "details": { "errors": { "title": ["제목을 입력해 주세요."] } }
  },
  "traceId": "request-id"
}
```

- 오류는 [ErrorCode·CustomException](../app/Common/Exceptions)과 [중앙 예외 매핑](../bootstrap/app.php)으로 처리합니다. Controller마다 try/catch와 임의 JSON을 반복하지 않습니다.
- 401은 인증, 403은 접근 거부, 422는 입력/업무 검증, 413은 업로드 크기 초과, 429는 요청 제한입니다.
- CSRF 검증 실패는 `CSRF_MISMATCH`입니다. 다른 419 오류를 모두 CSRF 재시도로 처리하지 않습니다.
- `X-Request-Id`는 응답 헤더와 traceId에 연결됩니다. 클라이언트 제공 값도 사용하므로 권한 증명이나 신뢰할 수 있는 사용자 식별자가 아닙니다.
- 파일 다운로드와 채팅 커서 응답은 일반 페이지 목록과 다릅니다. 계약을 확인하지 않고 통일하지 않습니다.
- 페이지 크기, 검색·정렬 허용 필드, 배열 직렬화는 해당 Request가 기준입니다. POST 결과가 항상 생성 모델인 것도 아닙니다.
- 생성/상태 변경 API의 HTTP 코드나 빈 값 처리도 기존 클라이언트와 함께 변경합니다.

직원 `GET /api/v1/staff/videos`의 카테고리는 `category_ids` CSV/배열로 다중선택합니다. 선택한 활성 동영상 카테고리 중 하나라도 연결된 영상을 중복 없이 반환하며 최대 100개를 검증합니다. 기존 단일 `category_id`도 입력 별칭으로 지원하고, 미선택/빈 배열은 전체입니다.

## 병의원 챗봇 Q&A API

- 목록의 `root_id`는 최상위 Q&A ID입니다. 해당 최상위와 모든 하위를 페이지네이션 전에 필터링하며 하위 항목 ID나 없는 ID를 지정하면 빈 목록을 반환합니다.
- 계층 편집 게시판은 `all=1`로 필터에 맞는 전체 목록을 조회합니다. 생략하면 기존 페이지네이션을 유지합니다. `status`는 `ACTIVE,INACTIVE`처럼 다중 선택을 지원합니다.
- Q&A 수정의 `position`은 이동 항목을 제외한 새 형제 목록의 0 기반 위치입니다. `parent_id`와 함께 전달하면 하위 트리까지 이동하며, 원래·새 형제 순서를 하나의 트랜잭션에서 갱신합니다. 순환 연결, 3단계 초과, 같은 상위의 중복 질문은 거절합니다.
- 직원 `/api/v1/staff/hospital-chatbot/qnas`는 목록·등록, `/{qna}`는 조회·수정·삭제, `/tree`는 상위 선택용 전체 목록입니다. 목록은 `q`, `depth`, `status`, `sort`, `direction`, `per_page`, `page`를 지원하며 기본 순서는 상위 다음 하위의 깊이 우선 순서입니다.
- 병의원 `GET /api/v1/hospital/hospital-chatbot/qnas`는 자신과 모든 상위가 노출 상태인 Q&A만 반환합니다. `id`, `parent_id`, `depth`, `question`, `answer`, `sort_order`로 대화 트리를 구성하며 형제 순서는 `sort_order`, `id`입니다. 직원 정보는 반환하지 않습니다.
- `parent_id`는 상위 Q&A ID이며 null은 1단계입니다. `question`은 필수 선택 문구, `answer`는 선택입니다. 최대 3단계, 순환 연결 금지, 하위가 남은 상위 삭제 금지를 트랜잭션에서 검증합니다. 상위 이동 시 자손의 깊이도 갱신하며 3단계 초과는 거절합니다.
- 같은 상위 아래의 선택 문구는 공백·대소문자 정규화 기준으로 중복될 수 없습니다. 다른 상위 아래에는 같은 문구를 등록할 수 있습니다. Seeder는 삭제된 동일 키를 복원하지 않습니다.
- 질문과 답변은 HTML이 아닌 일반 텍스트 계약입니다. 클라이언트는 DOM `textContent`와 동등한 방식으로 렌더링하며 서버 값을 HTML로 실행하지 않습니다.
- 초기 70건은 `HospitalChatBotQnaSeeder`를 대상으로 지정해 한 번 적재합니다. 전체 `DatabaseSeeder`에서는 자동 실행하지 않으며, 공유·운영 DB에는 적용 전 기존 키와 삭제 이력을 확인합니다.

## 병의원 챗봇 상담 운영시간 API

- 직원 `GET /api/v1/staff/hospital-chatbot/operating-hours`는 `timezone`, `configured`, `is_force_closed`, `force_closed_reason`, 월요일 1~일요일 7의 `weekly_schedule`, `closure_dates`를 반환합니다.
- `is_force_closed`는 요일·휴무일 설정보다 먼저 검사하는 즉시 중단 스위치입니다. 켜져 있으면 availability가 `FORCE_CLOSED`와 `next_open_at: null`을 반환하며, 다시 끌 때까지 상담원 연결을 닫습니다. 스위치를 끄면 사유는 함께 비웁니다.
- 직원 `PUT /api/v1/staff/hospital-chatbot/operating-hours`는 7개 요일 설정과 최대 366개 휴무일, 강제 중단 설정을 한 트랜잭션에서 교체합니다. 휴무일을 모두 해제할 때는 빈 배열을 보냅니다. 운영 요일은 `opens_at < closes_at`인 `H:i` 단일 구간이어야 하며 자정을 넘길 수 없습니다.
- 병의원 `GET /api/v1/hospital/hospital-chatbot/availability`는 KST 서버 시각 기준 `checked_at`, `is_available`, `reason`, `message`, `next_open_at`을 반환합니다. 시작 시각은 포함하고 종료 시각은 제외합니다.
- `reason`은 `OPEN`, `FORCE_CLOSED`, `OUTSIDE_HOURS`, `CLOSED_DATE`, `CONFIGURATION_UNAVAILABLE` 중 하나입니다. 날짜별 공휴일·임시휴무는 요일 설정보다 우선해 강제로 닫습니다.
- 설정이 없거나 7일 구성이 유효하지 않으면 Fail Closed로 상담 연결을 막고 `상담원 연결 시간이 아닙니다.`를 반환합니다. Q&A 조회는 운영시간과 무관하게 계속 허용합니다.

## 기존 Q&A 데이터 전환

- 폴더 탐색 UI는 사용하지 않습니다. 이전 폴더는 답변 없는 상위 Q&A로 전환하고 기존 질문 ID·답변·상담 스냅샷은 보존합니다.
- 새 설치는 기존 Q&A 생성 마이그레이션의 `parent_id`, `depth`를 사용합니다. 이미 적용된 개인 로컬 DB는 `php artisan hospital-chatbot:convert-qna-tree`로 대상을 확인한 뒤 `--apply`로 전환합니다. 초기화하거나 전체 Seeder를 실행하지 않습니다.
- 전환 명령은 변경 전 Q&A·폴더 데이터를 `storage/app/private/qna-tree-backup-*.json`에 백업합니다. 이전 폴더 테이블과 응답의 폴더 필드는 데이터 전환·읽기 호환용으로 유지하며, 관리 기준은 `parent_id`입니다. 이전 `qna-folders` 관리 API는 제공하지 않습니다. Q&A 등록·수정에 비어 있지 않은 `folder_id`를 전달하면 422로 거절합니다.
- 명령은 개인 로컬 환경만 허용합니다. 공유·운영 환경 전환은 별도 백업·적용·복구 절차를 검토해야 합니다.

## 병의원 챗봇 상담 API

- 병의원 `POST /api/v1/hospital/hospital-chatbot/consultations`는 운영시간을 검증한 뒤 `WAITING` 상담을 만들고 `{availability, consultation}`을 반환합니다. 이미 활성 상담이 있으면 운영시간과 무관하게 그 상담을 그대로 돌려줍니다. 진행 중인 상담은 마감 뒤에도 이어갈 수 있어야 합니다.
- 연타와 다중 탭은 한 건으로 수렴합니다. 사전 조회로 한 번, `active_hospital_id` 유니크 위반으로 또 한 번 막습니다. 병의원당 활성 상담은 DB 레벨에서 1건입니다.
- 요청 본문의 `context_qna_id`(선택)는 상담을 부르기 직전에 보던 Q&A입니다. 서버가 **요청 시점의 질문·답변 본문을 복사**해 둡니다. Q&A가 뒤에 고쳐지거나 지워져도 상담원이 보는 것은 요청자가 읽던 글입니다. 없는 id를 보내도 상담은 그대로 열립니다.
- 운영시간 밖이면 422 `CONSULTATION_UNAVAILABLE`이며 `details.availability`에 판정 결과가 들어갑니다.
- 병의원 `GET /consultations/active`는 생성과 **같은 `{availability, consultation}` 봉투**를 반환하며 활성 상담이 없으면 `consultation`이 null입니다. 새로고침 복구용이고, 함께 오는 `availability`로 연결 버튼 상태를 맞춥니다.
- 병의원 `POST /consultations/{consultation}/cancel`은 대기 중에만, `/end`는 연결된 뒤에만 가능합니다. 순서가 어긋나면 409입니다. 다른 병의원의 상담은 404입니다.
- 직원 `GET /api/v1/staff/hospital-chatbot/consultations`는 `status`(CSV), `assigned_staff_id`, `hospital_id`, `consultation_id`, `requester`, `from`, `to`, `sort`, `per_page`를 받습니다. `status`를 주지 않으면 대기·진행만 반환합니다.
- `from`·`to`는 `Y-m-d`이며 서버가 그날 처음과 끝으로 넓혀 `requested_at`에 겁니다. `to`를 시각 없이 그대로 쓰면 마지막 날이 통째로 빠지기 때문입니다.
- `requester`는 요청 당시 표시명 부분 일치입니다. 계정을 지워도 기록은 남아야 하므로 스냅샷을 봅니다. LIKE 와일드카드는 이스케이프해 글자 그대로 찾습니다.
- `sort`는 `queue`(기본), `recent`, `manage`입니다. `manage`는 대기(요청 오래된 순), 진행(최근 메시지순), 종료·취소(최근 종료순)입니다.
- `q`는 병의원명 부분 일치 또는 숫자 병의원 ID 일치입니다. `mine=1`은 로그인 직원의 담당 상담과 담당자 없는 대기 상담을 함께 반환합니다. 기간·상태 조건은 그대로 적용됩니다.
- `feed=1`은 무한스크롤용 커서 목록입니다. `per_page` 기본 20, 최대 100이며 `meta.next_cursor`를 다음 요청의 `cursor`로 보냅니다. 정렬은 `manage` 기준입니다. 조건 변경 또는 실시간 목록 변경 후에는 커서를 버리고 처음부터 조회합니다. `meta.status_counts`는 같은 검색 조건의 전체·대기진행·종료취소 건수입니다.
- 목록에는 `hospital.logo.path`와 `last_message`가 포함됩니다. 로고와 마지막 메시지는 일괄 조회하며 목록에서 마지막 Q&A는 표시하지 않습니다.
- 직원 `POST /consultations/{consultation}/claim`은 조건부 UPDATE로 한 명만 통과시킵니다. 이미 다른 상담원이 가져갔으면 409 `CONSULTATION_ALREADY_CLAIMED`입니다. **같은 상담원이 다시 눌러도 성공으로 취급합니다.**
- 연결 성공 시 담당 직원 이름으로 자동 인사를 상담 연결과 같은 트랜잭션에 한 번만 저장합니다. 자동 인사도 AGENT 메시지이며 순번을 사용합니다. 재연결·동일 요청 재시도에는 인사를 추가하지 않습니다.
- 목록의 `unread_count`는 현재 직원이 확인하지 않은 병의원 발신 메시지 수입니다. 직원·시스템 메시지는 제외합니다. 직원 `POST /consultations/{consultation}/read`에 `sequence`(0 이상의 정수)를 보내 읽음을 저장하며, 응답은 남은 `unread_count`입니다. 직원 ID는 로그인 계정에서만 얻고 순번은 뒤로 이동하지 않습니다.
- 읽음 저장은 `hospital_chatbot_consultation_reads` 신규 테이블을 사용합니다. 기존 환경에는 `2026_10_01_150000_create_hospital_chatbot_consultation_reads_table.php`를 먼저 적용한 뒤 새 API를 배포합니다. 기존 메시지·상담을 수정하거나 초기화하지 않습니다.
- 직원 `POST /consultations/{consultation}/end`는 담당 상담원만 가능합니다. 담당 검사는 조회가 아니라 UPDATE 조건에서 합니다. 담당이 아니면 403입니다.
- 직원 `GET /consultations/{consultation}`은 목록 항목에 `status_histories`를 더해 반환합니다. 이력은 `from_status`, `to_status`, `actor_type`, `actor_id`, `reason`, `occurred_at`이며 `from_status`가 null이면 상담이 생긴 순간입니다.
- 상담 DTO의 `waiting_seconds`는 대기 중이면 지금까지, 취소면 취소 시각까지, 나머지는 연결 시각까지입니다. `duration_seconds`는 연결→종료이며 **연결되지 않은 상담은 null**입니다. 0초로 내보내면 바로 끝난 상담과 구분되지 않습니다.
- `close_reason`은 `AGENT_COMPLETED`, `USER_CANCELED`, `USER_ENDED`, `WAITING_TIMEOUT`입니다. 대기 상담은 `hospital-chatbot:expire-waiting-consultations`가 설정된 시간(기본 30분) 뒤 자동 취소합니다.

## 병의원 챗봇 상담 메시지 API

- 병의원·직원 모두 `GET /consultations/{consultation}/messages`와 `POST`를 사용합니다. 경로 접두사만 다르고 계약은 같습니다.
- 목록은 커서가 없으면 최근 200개까지 반환합니다. `after_sequence`(0 이상)는 이후 메시지 복구용, `before_sequence`(1 이상)는 이전 대화 조회용이며 동시에 지정할 수 없습니다. 이전 조회는 커서보다 작은 순번 중 최근 구간을 오름차순으로 반환합니다. meta의 `has_older`로 이전 대화 존재 여부를 판단합니다. 이전 조회 응답의 `last_sequence`로 최신 메시지 동기화 커서를 덮어쓰지 않습니다.
- 전송 본문은 `content`(1~2000자)와 `client_message_id`(선택, 64자)입니다. 같은 `client_message_id`로 다시 보내면 새로 만들지 않고 먼저 저장된 메시지를 돌려줍니다. 재시도가 중복을 만들지 않습니다.
- `sequence`는 상담 안에서 1부터 증가하며 `lockForUpdate` 아래에서 매깁니다. 클라이언트는 이 값으로 정렬·중복 제거·갭 감지를 합니다.
- 전송은 `throttle:hospital-chatbot-message`(계정당 분당 120건)로 제한합니다. 연결된 상담에서만, 병의원은 자기 상담에서만, 직원은 담당 상담에서만 보낼 수 있습니다.

## 병의원 챗봇 상담 기록 내보내기 API

- 직원 `GET /consultations/export`는 목록과 **같은 필터**를 받아 CSV를 스트림합니다. 화면에서 본 것과 받은 파일이 달라지면 안 되므로 파싱과 검증을 목록과 공유합니다.
- 라우트는 `/{consultation}`보다 먼저 선언합니다. 뒤에 두면 `export`가 상담 ID로 잡힙니다.
- `from`·`to`는 **필수**입니다. 전체 기간은 동기 처리로 감당되지 않습니다. 기간은 `hospital_chatbot.export.max_days`(기본 92), 행 수는 `max_rows`(기본 10000)까지이며 넘으면 422와 함께 몇 건인지 알려 줍니다.
- 응답은 `text/csv; charset=UTF-8`이고 UTF-8 BOM으로 시작합니다. BOM이 없으면 엑셀이 한국어를 깨뜨립니다. `fputcsv`의 escape는 비워 RFC 4180대로 씁니다. PHP 기본 백슬래시 escape는 표준이 아닙니다.
- 열은 상담 ID, 병의원, 요청자, 담당 상담원, 요청·연결·종료 시각, 대기 시간(초), 상담 시간(초), 상태, 종료 사유, 상담 전 질문, 요청 화면입니다. 상태와 사유는 코드가 아니라 한국어입니다. 파일은 화면 없이 혼자 읽힙니다.
- 스트림이 시작된 뒤의 예외는 되돌릴 수 없으므로, 로그를 남기고 파일 끝에 잘렸다는 행을 붙입니다.

## 병의원 챗봇 실시간

- 채널은 [routes/channels.php](../routes/channels.php)에 있습니다. `hospital-chatbot.consultation.{consultationId}`는 상담 한 건, `hospital-chatbot.queue`는 상담원 대기 목록입니다.
- 기본 `withBroadcasting`은 user actor 전용이라 직원·병의원은 각자의 `/api/v1/{staff|hospital}/broadcasting/auth`를 씁니다. 클라이언트는 Echo의 custom authorizer로 그 경로를 지정하고 쿠키·CSRF를 함께 보냅니다.
- 상담 채널은 그 상담의 병의원 계정과 **모든 직원**이 구독합니다. 상담원은 담당이 아니어도 목록에서 상세로 들어가 볼 수 있어야 합니다. 대기 목록 채널은 직원만입니다.
- 이벤트는 `.hospital-chatbot.message.created`(상담 채널), `.hospital-chatbot.consultation.changed`(상담 채널), `.hospital-chatbot.queue.changed`(목록 채널)입니다.
- 메시지 발행은 저장 커밋 뒤에 Job으로 나갑니다. `broadcast_pending` 표식과 발행을 한 트랜잭션에서 처리하므로 중간에 실패하면 표식이 남고 `hospital-chatbot:dispatch-pending-broadcasts`가 매분 다시 집어 갑니다.
- 상태 변경 발행 실패 시 전용 큐의 재시도 Job이 현재 상태를 다시 읽어 발행합니다. 메시지 발행 시 목록 변경 이벤트도 발행합니다.
- 실시간 채널의 인가·구독 성공 중에는 10초 보완 조회를 중지하고, 연결 단절·구독 실패·미설정 시에만 사용합니다. 재연결과 화면 복귀 시 누락분을 조회합니다. 아래쪽 목록을 읽는 중에는 변경 안내를 표시하고 명시적으로 새 목록을 조회합니다.
- `hospital-chatbot.presence.{hospitalId}`는 병의원별 Presence 채널입니다. 직원은 조회할 수 있고 병의원 계정은 자기 병의원만 참여할 수 있습니다. 멤버 정보는 `actor`만 제공하며 직원의 방송 식별자는 `staff:{id}`로 구분합니다.
- 병의원 웹에서 상담 위젯이 열리고 브라우저 탭이 보일 때 참여하며, 닫기·탭 숨김·언마운트 시 나갑니다. 같은 계정의 여러 연결은 Presence 서버가 합산합니다. 직원 화면은 병의원 멤버 존재 시 초록, 마지막 멤버 이탈 후 8초 유예를 거쳐 회색, 연결·인가 미확인 시 점을 숨깁니다.
- 실행 환경에는 Reverb, `hospital-chatbot` 큐 워커와 양쪽 웹의 `NEXT_PUBLIC_REVERB_*` 설정이 필요합니다. 이 설정이 없으면 온라인 표시는 숨기고 보완 조회로 동작합니다. 재시도까지 실패한 발행은 운영 로그·실패 Job을 확인해야 하며 실시간 전송이 영구 보장되는 것은 아닙니다.
- 클라이언트는 순번이 아는 값보다 2 이상 앞서면 붙이지 않고 `after_sequence`로 다시 조회합니다. 실시간과 재조회가 같은 메시지를 줘도 순번으로 한 번만 남깁니다.

## 병의원 챗봇 로그와 지표

- 로그는 저장소 관례대로 `Log::info('한국어 문장', [컨텍스트])` 로 남기되, 컨텍스트에 `event` 키를 하나 얹습니다. 문장은 사람이 읽고 `event`는 수집기가 묶습니다. 문장을 고쳐도 집계가 끊기지 않습니다. `event` 값은 [HospitalChatBotLog](../app/Domains/HospitalChatBot/Support/HospitalChatBotLog.php)에 상수로 있습니다. 오타 하나로 그 이벤트만 집계에서 빠지는 것을 막으려는 것입니다.
- 상담 요청·중복 반환·연결·충돌·종료, 메시지 저장 실패, 발행 실패와 재시도, Export 실패, 운영시간 계산 오류를 남깁니다. Claim 충돌은 성공과 다른 event입니다. 같은 이름으로 묶이면 셀 수 없습니다.
- `hospital-chatbot:report-metrics`가 5분마다 현재 상태를 남깁니다. 대기 수, 진행 수, 최장 대기 시간, 미발행 메시지 수와 그중 가장 오래된 것, 그리고 발행 사용 여부입니다. 발행이 꺼져 있으면 미발행 수는 줄지 않으므로 숫자만 보고 장애로 읽지 않습니다.
- 운영시간 계산이 깨지면 500이 아니라 `CONFIGURATION_UNAVAILABLE`로 닫습니다. 여기서 예외를 올리면 SDK가 Q&A조차 열지 못합니다.

## 병의원 프로모션 API

Staff 접두사는 `/api/v1/staff/hospital-promotions`입니다. `GET /board`는 일반 ApiResponse data에 `{today,left,right}`를 반환합니다. 양쪽은 `current: [{slot,promotion,filtered_out}]` 3개와 `upcoming: {items,meta}`입니다. 예약 자체가 없으면 null/false, 필터에 제외된 현재 예약은 내용 없이 null/true입니다. `GET /`는 ENDED만 data 배열과 최상위 meta로 반환하고 종료일·ID 내림차순입니다. `q`는 제목·등록 직원 이름/닉네임·숫자 ID 검색, 목록의 `status`는 ACTIVE/INACTIVE CSV 또는 배열의 다중선택이며 기존 단일 값도 받습니다. 미선택은 전체, 여러 값은 OR 조건이고 저장 폼의 단일 status와 구분합니다. 날짜 필터는 게시 구간과 교집합입니다. 예정 목록은 시작일·슬롯·ID 오름차순이며 `left_page`/`right_page`는 독립적입니다. 모든 목록은 15개 고정입니다.

- `GET /{promotion}`, `POST /`, `PATCH /{promotion}`은 상세 DTO를 반환합니다. multipart `POST` + `_method=PATCH`도 지원합니다. 목록에는 content를 넣지 않고 상세/저장에만 넣습니다. PATCH 필드 생략은 유지하며 `expected_updated_at`은 필수입니다.
- `GET /{promotion}/operation-histories`는 공통 이력 DTO와 meta이며 `operation_histories_page`(기본 1), `operation_histories_per_page`(기본 10, 최대 50)를 사용합니다.
- `GET /availability?side=&slot=&start_date=&end_date=&exclude_id=`는 `{available,conflicts,has_more}`를 반환합니다. conflicts는 시작일·ID 순 최대 10개의 `{id,title,side,slot,start_date,end_date}`이며 초과 시 has_more=true입니다. 조회·생성·수정 중 하나의 권한이 필요하고 exclude_id 사용은 해당 건 수정 권한이 추가로 필요합니다. 조회 권한이 없으면 conflicts는 빈 배열, has_more=false로 정보를 제한합니다. 저장 중 겹침도 422 `details.errors.slot`과 동일한 `details.availability`를 반환합니다. 최종 저장 트랜잭션의 검사만 확정적입니다.
- `POST /editor-images`는 `image,promotion_id?`를 받고 `{url,path}`를 반환합니다. promotion_id가 없으면 생성, 있으면 해당 건 수정 권한이 필요합니다. `DELETE /editor-images`의 `paths`/`urls`는 JSON 배열이며 생략/빈 배열은 아무것도 삭제하지 않습니다. 정리 응답은 `{requested_count,deleted_count,deleted_paths}`이고 물리 삭제는 커밋 후 공통 정리 작업에서 수행합니다.
- 병의원 인증 경로 `/api/v1/hospital/promotions`는 ACTIVE이면서 KST CURRENT인 데이터만 `{left:[],right:[]}`로 반환합니다. 슬롯 오름차순 최대 3개, 빈 슬롯 placeholder는 없습니다. 공개 DTO는 `id,title,side,slot,start_date,end_date,banner`만, `GET /{promotion}`에는 content만 추가합니다. 작성자·직원 메타데이터는 노출하지 않습니다. 비공개/예정/종료 상세는 404입니다.
- 병의원 배너 클릭 시 `POST /{promotion}/click`으로 `click_count`를 원자적으로 증가시키고 `{counted:true}`를 반환합니다. 계정·프로모션당 분당 1회, 계정 전체 분당 30회 제한이며 초과 시 429입니다. 읽기 GET/슬라이드 노출/직원 미리보기는 세지 않고 편집 토큰·감사·운영 히스토리를 변경하지 않습니다. 아직 연결 전인 병의원 배너 클라이언트는 기존 개발용 `/view` 대신 `/click`을 사용합니다.

권한·동시성·설치 migration은 [프로모션 도메인 규칙](domain-rules.md#병의원-프로모션)을 확인하세요.

## 웹과 앱 인증

| 클라이언트               | 방식                 |
| ------------------------ | -------------------- |
| Staff/Hospital/Beauty 웹 | Redis 기반 서버 세션 |
| User 웹                  | Redis 기반 서버 세션 |
| User 네이티브 앱         | Sanctum Bearer 토큰  |

Staff·파트너 API의 웹 로그인을 토큰 로그인으로 되돌리지 않습니다.
웹은 `X-Beaulab-Client: web`, credentials 포함 요청을 사용하고 Bearer 토큰을 섞지 않습니다.

웹 연결 순서:

1. `GET /api/v1/{actor}/auth/csrf`로 쿠키와 CSRF 토큰을 준비합니다.
2. 로그인 등 변경 요청에 `X-CSRF-TOKEN`을 전송합니다. 로그인·로그아웃도 예외가 아닙니다.
3. 새 탭/새로고침 시 `GET /api/v1/{actor}/auth/session`으로 사용자 상태를 복원합니다.
4. 실제 사용자 활동은 `POST /api/v1/{actor}/auth/activity`를 사용합니다. 백그라운드 polling만으로 영구 로그인시키지 않습니다.
5. 로그아웃 후 프론트 메모리의 사용자·캐시도 정리합니다. 웹 인증 토큰을 localStorage 등에 저장하지 않습니다.

[web_auth.php](../config/web_auth.php)의 기본값은 다음과 같습니다. 배포 환경의 override가 있으면 그것이 우선합니다.

| Actor                   | 미사용 만료 | 최대 수명 | 영속 쿠키 |
| ----------------------- | ----------- | --------- | --------- |
| staff, hospital, beauty | 120분       | 24시간    | 아니오    |
| user                    | 7일         | 30일      | 예        |

Origin은 Actor별 정확한 허용 목록으로 검증합니다. 운영 기본값은 빈 목록이므로 반드시 설정해야 합니다.
웹과 API는 same-site 배치를 전제로 합니다. 허용 Origin이어도 Sec-Fetch-Site가 cross-site이면 거부되므로 서로 다른 사이트에 배포할 때 목록 추가만으로 해결되지 않습니다.
쿠키는 Actor별 이름·경로, HttpOnly, SameSite=Lax를 사용하고 운영에서는 Secure로 전달합니다. 실제 배포 도메인·HTTPS·프록시 설정도 맞아야 합니다.

매 요청에서 계정 활성 상태와 비밀번호 변경 상태를 확인하며 로그아웃 폐기 기록으로 동시 요청의 세션 재저장을 막습니다.
Redis 세션/폐기 저장소 오류를 단순 캐시 미스로 취급해 인증을 통과시키지 않습니다.
일반 Laravel `SESSION_*` 설정만으로 이 Actor 웹 세션 정책이 바뀌는 것은 아닙니다.

## 인증과 권한의 경계

- `auth:sanctum`은 인증, `actor:staff` 등의 미들웨어는 실제 계정 모델의 Actor를 검증합니다. 세션용 transient token의 ability만 믿지 않습니다.
- Staff는 공통 진입 권한과 작업별 Policy를 통과해야 합니다. User에는 Staff용 Spatie 권한 체계를 그대로 적용하지 않습니다.
- Spatie guard 이름은 staff/hospital/beauty입니다. 세션 guard 이름과 혼동하지 않습니다.
- 외부 진입 Action에서 `Gate::authorize(...)`를 호출하고 Policy에서 기능 권한과 대상 접근 조건을 확인합니다.
- Hospital/Beauty 계정은 연결된 자기 업체의 리소스만 다룹니다. 요청의 hospital_id/beauty_id를 신뢰하지 않습니다.
- 조회 권한, 일반 생성·수정 권한, 검수·상태 변경 권한은 별개입니다. 일반 수정 요청에 상태 필드가 섞여 들어오는 경로도 확인합니다.
- 관리자메모 쓰기는 메모 자체 권한을 사용합니다. 대상 조회·소유권 확인은 유지하지만 대상 도메인의 일반 수정 권한까지 중복 요구하지 않습니다.

권한/역할 목록은 [AccessPermissions](../app/Common/Authorization/AccessPermissions.php), [AccessRoles](../app/Common/Authorization/AccessRoles.php), [AuthorizationSeeder](../database/seeders/AuthorizationSeeder.php)가 기준입니다.
기본 admin은 일반 생성·수정 중심이고 상태 변경은 super_admin 중심으로 배정됩니다. 실제 API는 역할 문자열 대신 필요한 권한을 검사하며, 환불 즉시 처리 같은 명시적 역할 예외는 해당 도메인 규칙을 따릅니다.

권한 Seeder는 역할의 권한을 동기화하고 캐시를 초기화합니다. 기존 역할의 커스텀 배정을 덮어쓸 수 있고 미사용 권한 행을 모두 삭제하는 도구도 아니므로 운영에서 무조건 재실행하지 않습니다.
프론트 버튼 숨김/비활성화는 편의 UI이며 서버 인가를 대신하지 않습니다.

## 병의원 계정 생성

현재 정책은 아이디(`nickname`)+비밀번호 로그인, 이메일 인증입니다. 인증 연락처로 휴대폰 번호나 PASS를 사용하지 않습니다.
`AccountHospital.email`과 `email_verified_at`이 인증된 계정 정보이며, `name`은 호환용 병의원명입니다. 처리자 표시는 병의원 관계의 이름을 사용합니다.

초대 발송 UI는 두 흐름에서 같습니다. 이메일을 입력/수정할 수 있고 입점신청의 applicant_email은 기본 제안값일 뿐 강제 수신처가 아닙니다.

| 흐름                 | 초대 전                                | 계정 생성 완료 시                            |
| -------------------- | -------------------------------------- | -------------------------------------------- |
| 관리자가 생성한 병의원 | 병의원 존재, 계정 미연결                 | 해당 병의원에 계정 1:1 연결                    |
| 입점신청 병의원        | 입점 승인, 아직 병의원으로 전환되지 않음 | 병의원·사업자 정보·초기 지갑 생성 후 계정 연결 |

입점 전환 시 신규 병의원은 검수 `NOT_APPLIED`(미신청), 운영 `ACTIVE`로 시작합니다.
기본 의료진은 생성하지 않습니다. 사업자등록증은 이전하고 의사면허 파일은 입점신청에 남깁니다.
이메일 인증 번호를 광고 수신 전화번호로 전용하지 않으며, 전환 직후 비어 있을 수 있는 `ad_reception_phone_1`은 검수 신청 전 등록해야 합니다.

가입 순서:

1. 직원이 권한과 대상 상태를 확인하고 계정 생성 이메일을 발송합니다.
2. 사용자가 `GET /hospital/auth/account-invitations/{token}`으로 초대를 확인합니다.
3. 초대의 `/email-verifications`에 인증번호를 요청하고 `/email-verifications/{emailVerification}/verify`에서 검증합니다.
4. 검증 증표와 계정 입력값으로 초대를 완료합니다. 대상→초대→인증 기록 순서로 잠그고 유효성·중복·소유권을 재확인합니다.
5. 계정 연결, 필요한 병의원·지갑 생성, 증표 소비, 초대 사용 처리를 함께 커밋합니다.

이 문서에서 `/hospital`, `/staff`로 시작하는 축약 경로 앞에는 `/api/v1`이 붙습니다. 세부 payload는 [Hospital 인증 Request](../app/Modules/Hospital/Http/Requests/Auth)를 확인합니다.

- 초대 token은 64자이며 DB에는 SHA-256 해시를 저장합니다. 메일 큐의 민감 payload도 보호합니다.
- 기본 초대 72시간, 이메일 코드 5분, 재발송 간격 60초, 오입력 최대 5회, 가입 증표 15분입니다. [초대 설정](../config/hospital_account_invitation.php)이 기준입니다.
- 인증 증표는 초대와 이메일에 묶이며 한 번만 소비합니다. 인증 성공 화면을 거쳤다는 이유만으로 서버 검증을 생략하지 않습니다.
- 초대 이력은 기본 5개, 최대 20개 페이지네이션입니다. 사용 가능/사용/폐기/만료는 저장 시점뿐 아니라 현재 시각과 사용·폐기 기록으로 판정합니다.
- 가입 후 별도의 이메일 등록 화면은 없습니다. 개발 계정도 Factory에서 인증 이메일이 있는 상태로 생성합니다.

## 병의원 비밀번호 재설정

- 직원 발송: `POST /staff/hospitals/{hospital}/password-reset-link`. 병의원 조회와 재설정 발송 권한이 필요합니다.
- 직원 모달은 인증 이메일을 기본 표시하되 `recipient_email`로 다른 수신처를 지정할 수 있습니다. 인증된 계정 이메일 자체를 변경하지 않습니다.
- 다른 주소로 재설정 링크를 보낼 수 있는 권한은 계정 접근권을 넘길 수 있는 민감 권한입니다. 실제 수신처와 발송 직원을 기록합니다. 이전 기록의 수신처를 현재 이메일로 추정해 채우지 않습니다.
- 공개 찾기: `POST /hospital/auth/password-reset-link`. 가입 여부를 노출하지 않는 공통 응답을 사용하고 메일 처리는 큐에서 수행합니다.
- `POST /hospital/auth/password-reset/verify`는 병의원명·마스킹 아이디와 링크 유효성을 반환합니다.
- `POST /hospital/auth/password-reset`는 계정→토큰 잠금 순서로 비밀번호 변경과 토큰 소비를 처리합니다. 기본 링크 수명은 60분입니다.
- 계정·이메일·비밀번호 상태 변경을 재검증하고 이전 링크 재사용을 막습니다. 비밀번호 변경 후 기존 세션/토큰의 인증도 무효화합니다.
- 임의 수신처를 지정하는 공통 발송 Action은 Staff 인가 없이 호출할 수 없도록 유지합니다.

병의원 인증 메일은 [HospitalAccountMail](../app/Domains/AccountHospital/Support/HospitalAccountMail.php), [메일 템플릿](../resources/views/mail)을 사용합니다.
휴대폰 인증에서 전환하는 기존 환경의 데이터·링크 폐기 및 rollback 제한은 [운영 절차](operations.md)의 이메일 인증 전환 항목을 먼저 확인하세요.

## 직원 비대면 상담 API

모든 경로는 `/api/v1/staff` 아래이며 기존 직원 웹 세션·CSRF·`common.access`를 사용합니다.

| 메서드 | 경로                                        | 용도 / 추가 권한                                            |
| ------ | ------------------------------------------- | ----------------------------------------------------------- |
| GET    | `/remote-consultations`                     | 목록 / `beaulab.remote_consultation.show`                   |
| GET    | `/remote-consultations/{id}`                | 신청·이미지·배정 정보 / 동일 조회 권한                      |
| GET    | `/remote-consultations/{id}/quotes/{quote}` | 해당 신청에 속한 병의원 답변 / 동일 조회 권한                 |
| POST   | `/remote-consultations/{id}/approve`        | 신청 승인 (기존 배정 유지) / 조회 + `beaulab.remote_consultation.approve` |
| PATCH  | `/remote-consultations/{id}/status`         | 검수·반려 (`status`: `REVIEWING`/`REJECTED`) / 조회 + 승인 권한 |
| GET    | `/remote-consultations/{id}/hospitals`      | 배정 가능 병의원 검색 (`q`, `page`) / 조회 + 승인 권한 |
| POST   | `/remote-consultations/{id}/quotes`         | 다중 직접 배정 (`hospital_ids`: 최대 6개, 기존 `hospital_id` 단건 호환) / 조회 + 승인 권한 |
| DELETE | `/remote-consultations/{id}/quotes/{quote}` | 미상담 견적 삭제 / 조회 + 승인 권한 |

목록은 `q`(ID·이름·전화번호), `gender`, `approval_status`, `start_date`, `end_date`, `birth_year_from`, `birth_year_to`, `page`로 조회합니다. 날짜 범위는 한국 시간 기준 신청일이며 15건 단위입니다. 목록 응답은 공통 `data: []`, `meta` 페이지 정보 형식, 승인 응답은 갱신된 신청 상세입니다. 잘못된 필터/승인 조건은 422, 권한 없음은 403, 다른 신청의 견적 ID는 404입니다.

회원·병의원용 경로는 제공하지 않습니다. 승인 응답의 `assigned_count`는 배정 건수이며 외부 알림 발송 건수가 아닙니다. 데이터와 후속 입력 검증 계약은 [도메인 규칙](domain-rules.md#비대면-상담-db)을 따릅니다.

## 메인 배너/팝업 노출설정

직원 경로는 `/api/v1/staff/banner-exposures`이며 `beaulab.banner_exposure`의 조회/등록/수정/삭제 권한으로 구분합니다.

| 메서드 / 하위 경로 | 내용 |
| --- | --- |
| `GET /board` | `placement` 필수. `q`, `connection_type`, `start_date`, `end_date`, `page` 선택. 활성 전체 목록, 비활성 15건 페이지, 전체 활성 키, 수량/한도, 등록 가능 여부, 정렬 버전 반환 |
| `GET /targets` | `target_type`은 CATEGORY/HOSPITAL/EVENT/NOTICE. `q`, `category_id`, `page`로 검색, 20건 페이지 |
| `POST /` | 기획전/페이지 직접 등록. 배너 이미지 필수, PNG/JPG/WEBP 10MB 이하 |
| `GET /{id}` | 직접 등록 상세 |
| `PATCH /{id}` | 수정. 파일 포함 시 POST와 `_method=PATCH` 사용, `expected_updated_at` 필수 |
| `PATCH /{id}/status` | ACTIVE/INACTIVE 전환, `expected_updated_at` 필수 |
| `DELETE /{id}` | 소프트 삭제, `expected_updated_at` 필수 |
| `PUT /order` | `placement`, `revision`, 전체 활성 `keys` 배열. `banner:ID`와 `ad:ID`를 함께 정렬 |
| `GET /{id}/operation-histories` | 공통 운영 이력, `operation_histories_page`로 조회 |

기간/용량/수정 충돌은 422 오류로 반환합니다. 광고는 직접 등록 API의 수정 대상이 아니며 원본 광고 관리에서 처리합니다.

등록·수정 시 `start_at`, `end_at`은 필수입니다. 신규 등록 또는 시작 시각 변경 시 과거는 허용하지 않으며, 수정에서 기존 시작 시각을 그대로 유지하는 것은 허용합니다. 종료는 시작 시각 이상이어야 하고 연결 이벤트 기간과 예약 용량 검사를 적용합니다.

사용자 `GET /api/v1/user/banner-exposures?placement=MAIN_POPUP`은 노출 항목과 `landing: {type,id,url,open_mode}`, 30분 유효한 상대 서명 `click_url`을 반환합니다. 위치는 MAIN_POPUP/MAIN_VERTICAL_BANNER/MAIN_HORIZONTAL_BANNER 및 기타의 CONSULT_MEMO(상담메모장)/SEARCH(검색창)입니다. 직원 API도 동일한 5개 위치 코드를 받습니다. 클릭 시 해당 URL에 POST하며 서버는 현재 노출 여부와 서명을 검사합니다. 앱은 내부 대상은 유형/ID로 화면 전환하고 외부 주소는 IN_APP/BROWSER에 따라 열어야 합니다. 클릭수는 요청 횟수이며 중복 제거된 사용자 수가 아닙니다.

## 성형·쁘띠 이벤트 노출설정

직원 기본 경로는 `/api/v1/staff/event-exposures`입니다. 조회/등록/수정은 각각 `beaulab.event_exposure.show/create/update` 권한을 사용합니다.

| 메서드 / 하위 경로 | 내용 |
| --- | --- |
| `GET /categories` | 활성 성형·쁘띠 대분류 `id/name/group_code` 목록 |
| `GET /board` | `placement` 필수. 카테고리별 위치의 `category_id` 생략은 독립된 전체 탭. `q/start_date/end_date/page` 선택. 노출중 전체, 미노출 15건 페이지, `count/limit/active_keys/revision` 반환 |
| `GET /targets` | 동일 위치 조건과 `q/page`로 등록 가능한 이벤트 검색. 이벤트 ID·이벤트명·병의원명, 20건 페이지 |
| `POST /` | 위치 조건, `hospital_event_id`, `start_at/end_at` 필수. 공개 상태로 등록 |
| `GET /{id}` | 직접 등록 상세. 광고 ID가 아닌 노출설정 ID |
| `PATCH /{id}` | 이벤트·게시기간 수정, `expected_updated_at` 필수. 위치 변경 불가 |
| `PATCH /{id}/status` | `status=ACTIVE/INACTIVE`, `expected_updated_at` 필수 |
| `PUT /order` | 위치 조건, `revision`, 현재 전체 활성 `keys` 배열 (`event:ID` / `ad:ID`) |

위치 코드는 `SURGERY_TOP_BANNER`, `SURGERY_HOT_EVENT`, `SURGERY_CATEGORY_BANNER`, `PETIT_TOP_BANNER`, `PETIT_HOT_EVENT`, `PETIT_CATEGORY_BANNER`입니다. 카테고리별 위치만 해당 그룹의 활성 대분류 ID를 받습니다. ID를 생략하면 해당 성형/쁘띠 전체 탭의 독립 노출 위치이며, 개별 카테고리 목록을 합산하는 의미가 아닙니다. 전체 탭의 등록·정렬·공개 전환·사용자 조회에도 동일한 계약을 적용합니다.
날짜는 ISO 8601 입력 후 한국 시간으로 저장합니다. 신규/변경 시작 시각은 과거 불가, 기존 시작 시각 그대로 수정은 허용합니다. 이벤트 기간·중복·예약 용량·동시 수정 위반은 422입니다. 기간 종료는 지정한 초까지 포함합니다.
사용자 `GET /api/v1/user/event-exposures`는 동일 위치 조건으로 현재 노출 항목의 `source_key/event_id/name/hospital_name/image/click_url`을 순서대로 반환합니다. 앱에서는 `event_id`를 이벤트 상세로 연결하고 클릭 시 `click_url`에 POST합니다. URL은 30분 유효한 상대 서명이며 위치·카테고리·항목을 서명에 포함합니다. `POST /api/v1/user/event-exposures/{placement}/{sourceKey}/click`은 서명과 현재 노출 여부를 검사하고 분당 60회 요청 제한을 적용합니다. 현재 미노출 또는 다른 위치의 항목은 404이며 집계하지 않습니다. 내부 담당자·예약·권한 정보는 사용자 응답에 반환하지 않습니다. 직원 목록은 위치별 `click_count`를 반환하며 기존 `view_count`는 사용하지 않습니다.

## 동영상 노출설정

직원 기본 경로는 `/api/v1/staff/video-exposures`, 권한은 `beaulab.video_exposure.show/create/update`입니다.

- `GET categories`, `GET board`, `GET targets`: 위치 `placement=SURGERY|PETIT`, 선택적 `category_id`, 검색 `q`, 페이지 `page`. 목록은 게시기간 겹침 필터 `start_date/end_date`도 받습니다. 전체는 다른 카테고리를 합산하지 않는 독립 위치입니다.
- `POST /`: 위치와 `hospital_video_id`, `start_at`, `end_at`으로 등록합니다. `GET {videoExposure}`, `PATCH {videoExposure}`는 조회·수정이며 수정은 `expected_updated_at` 필수입니다.
- `PATCH {videoExposure}/status`: 공개여부 `ACTIVE|INACTIVE` 및 `expected_updated_at`. `PUT order`: 위치, 현재 노출 항목 전체 `keys` (`video:ID`), `revision`.
- 사용자 `GET /api/v1/user/video-exposures`는 현재 노출되는 항목의 `source_key/video_id/name/hospital_name/image/click_url`을 반환합니다. 앱은 `video_id`로 동영상 상세를 열고 30분 유효한 상대 서명 `click_url`에 POST하여 클릭을 집계합니다. 실제 앱 연동은 소비 측 작업입니다.

등록·수정 시 적격 영상, 6개 예약 한도, 동일 영상 중복, 과거 시작 시각, 버전을 검증합니다. 비공개·강제중지·신고 차단·삭제된 원본은 실제 노출과 클릭 집계에서 제외합니다. 클릭 API는 서명 검증 및 분당 60회 제한을 적용합니다.

## 후기 노출설정

직원 경로는 `/api/v1/staff/review-exposures`이며 `beaulab.review_exposure.show/create/update` 권한을 사용합니다.

- `GET categories`: 기존 후기 usage의 활성 대분류. 페이지 진입 시 조회하고 카테고리 전환마다 재조회하지 않습니다.
- `GET board`, `GET targets`: `placement=SURGERY|PETIT`, 선택적 `category_id`, `q`, `page`. 게시판은 게시기간 겹침 필터 `start_date/end_date`를 추가로 받습니다. 검색은 후기 ID·제목·병의원명이며 목록은 담당자 검색도 지원합니다.
- `POST /`: `placement`, 선택적 `category_id`, `hospital_review_id`, `start_at`, `end_at`으로 등록합니다. 연결유형이나 이미지 업로드는 받지 않습니다.
- `GET {reviewExposure}`, `PATCH {reviewExposure}`: 모달 조회·수정. 수정은 `expected_updated_at`을 요구하고 위치는 변경할 수 없습니다.
- `PATCH {reviewExposure}/status`: `status=ACTIVE|INACTIVE`, `expected_updated_at`으로 공개여부 변경.
- `PUT order`: 위치와 현재 노출 항목 전체 `keys` (`review:ID`), `revision`으로 순서 저장.

기간·중복·6개 예약 한도·버전 충돌은 422로 반환합니다. 정상 노출 가능한 후기만 등록할 수 있으며 후기 원본의 게시 상태를 변경하지 않습니다.
사용자 `GET /api/v1/user/review-exposures`는 동일 위치 조건으로 `source_key/review_id/name/hospital_name/image/image_count/click_url`을 반환합니다. `review_id`로 후기 상세 이동을 연결합니다. 클릭은 30분 유효한 상대 서명 URL에 POST하며 현재 노출 여부와 분당 60회 제한을 확인합니다. 실제 앱의 상세 이동·클릭 전송 연동은 이 API 소비 측에서 구현해야 합니다.

## 병의원 상담 문구 설정

직원 `GET /api/v1/staff/hospital-chatbot/message-settings`, `PUT` 동일 경로로 전체 공통 문구를 조회·저장합니다. 기존 직원 로그인·공통 접근 검사를 적용하며 운영시간 API와 별도로 저장합니다.

| 필드 | 계약 |
| --- | --- |
| `welcome_message` | 챗봇 첫 안내, 필수 2,000자 이하 |
| `connection_message` | 연결 시 자동 발송할 인사, 필수 1,000자 이하. `{상담원명}`은 최대 한 번, 실제 연결 직원 이름으로 치환 |
| `macros` | 최대 50개, 빈 배열 허용. 각 항목은 `shortcut`, `content` |
| `macros.*.shortcut` | `#` 제외 1~30자. 한글·영문·숫자·밑줄·하이픈, 대소문자를 무시한 중복 금지 |
| `macros.*.content` | 필수 2,000자 이하, 일반 텍스트 |

`hospital_chatbot_message_settings`의 고정 ID 1에 문구와 매크로 JSON 목록을 원자적으로 저장합니다. 최초 미설정 시 기존 안내와 기본 매크로 4개를 반환합니다. 빈 매크로 목록을 저장하면 기본값으로 복원하지 않습니다.
병의원 availability 응답에는 `welcome_message`만 추가하며 내부 매크로와 인사 템플릿은 제공하지 않습니다. 연결 인사는 기존 상태 전이 트랜잭션에서 한 번 저장되며, 설정 변경으로 이전 메시지를 수정하지 않습니다.
적용 전 `2026_10_01_160000_create_hospital_chatbot_message_settings_table.php` 신규 생성 마이그레이션이 필요합니다. 기존 상담·운영시간 테이블과 데이터는 변경하지 않습니다. 복구 시 설정을 보존하려면 테이블을 삭제하지 않고 앱 코드만 되돌립니다.
