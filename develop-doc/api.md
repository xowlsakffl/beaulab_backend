# API·인증

프론트엔드 연동과 새 API 개발의 공통 계약입니다. 개별 필드·선택값·권한 이름의 최종 기준은 각 Request, DTO, Policy입니다.

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

## 병원 챗봇 Q&A API

- 직원 API `/api/v1/staff/hospital-chatbot/qnas`는 목록·등록을 제공하고 `/{qna}`에서 조회·수정·삭제합니다. 목록은 `q`, `category`, `status`, `sort`, `direction`, `per_page`를 지원하며 일반 페이지 meta를 반환합니다.
- 병원 API `GET /api/v1/hospital/hospital-chatbot/qnas`는 로그인한 병원에 활성 Q&A 전건을 `sort_order`, `id` 순으로 반환합니다. 응답에는 `id`, `category`, `question`, `answer`, `sort_order`만 포함하고 직원·상태 정보는 노출하지 않습니다.
- 질문은 공백·대소문자를 정규화한 키를 기준으로 활성 Q&A끼리 중복될 수 없습니다. 삭제된 Q&A는 초기 데이터 Seeder를 다시 실행해도 복원하거나 같은 질문을 재등록하지 않습니다.
- 질문과 답변은 HTML이 아닌 일반 텍스트 계약입니다. 클라이언트는 DOM `textContent`와 동등한 방식으로 렌더링하며 서버 값을 HTML로 실행하지 않습니다.
- 초기 70건은 `HospitalChatBotQnaSeeder`를 대상으로 지정해 한 번 적재합니다. 전체 `DatabaseSeeder`에서는 자동 실행하지 않으며, 공유·운영 DB에는 적용 전 기존 키와 삭제 이력을 확인합니다.

## 병원 챗봇 상담 운영시간 API

- 직원 `GET /api/v1/staff/hospital-chatbot/operating-hours`는 `timezone`, `configured`, `is_force_closed`, `force_closed_reason`, 월요일 1~일요일 7의 `weekly_schedule`, `closure_dates`를 반환합니다.
- `is_force_closed`는 요일·휴무일 설정보다 먼저 검사하는 즉시 중단 스위치입니다. 켜져 있으면 availability가 `FORCE_CLOSED`와 `next_open_at: null`을 반환하며, 다시 끌 때까지 상담원 연결을 닫습니다. 스위치를 끄면 사유는 함께 비웁니다.
- 직원 `PUT /api/v1/staff/hospital-chatbot/operating-hours`는 7개 요일 설정과 최대 366개 휴무일, 강제 중단 설정을 한 트랜잭션에서 교체합니다. 휴무일을 모두 해제할 때는 빈 배열을 보냅니다. 운영 요일은 `opens_at < closes_at`인 `H:i` 단일 구간이어야 하며 자정을 넘길 수 없습니다.
- 병원 `GET /api/v1/hospital/hospital-chatbot/availability`는 KST 서버 시각 기준 `checked_at`, `is_available`, `reason`, `message`, `next_open_at`을 반환합니다. 시작 시각은 포함하고 종료 시각은 제외합니다.
- `reason`은 `OPEN`, `FORCE_CLOSED`, `OUTSIDE_HOURS`, `CLOSED_DATE`, `CONFIGURATION_UNAVAILABLE` 중 하나입니다. 날짜별 공휴일·임시휴무는 요일 설정보다 우선해 강제로 닫습니다.
- 설정이 없거나 7일 구성이 유효하지 않으면 Fail Closed로 상담 연결을 막고 `상담원 연결 시간이 아닙니다.`를 반환합니다. Q&A 조회는 운영시간과 무관하게 계속 허용합니다.

## 병원 챗봇 Q&A 폴더 API

> 전체 구조와 추가된 테이블 8개는 [구조 문서](architecture.md#병원-챗봇-구조)의 그림을 먼저 보면 아래 절들이 빨리 읽힙니다.


- 폴더는 2단 고정입니다. 대분류 > 소분류 > 질문이며 소분류 아래에는 만들 수 없습니다. 깊이는 `depth` 컬럼으로 들고 있어 트리를 타지 않고 검사합니다.
- 직원 `GET /api/v1/staff/hospital-chatbot/qna-folders`는 전체 트리를 한 번에 반환합니다. 노드는 `id`, `parent_id`, `depth`, `name`, `sort_order`, `qna_count`, `children`이며 `qna_count`는 그 폴더에 **직접** 달린 Q&A 수입니다. 하위 폴더 것은 포함하지 않되 **소프트 삭제된 Q&A는 포함합니다.** 삭제 차단 규칙과 같은 수를 보여 주려는 것입니다. 다른 수를 보여 주면 0건인데 지워지지 않는 폴더가 생깁니다. 화면에 노출하는 "질문 수"로 쓰면 목록보다 큰 값이 나올 수 있습니다.
- `POST`, `PATCH /{folder}`, `DELETE /{folder}`, `PATCH /{folder}/move`는 모두 **갱신된 전체 트리**를 반환합니다. 클라이언트가 다시 조회하지 않습니다.
- `PATCH /{folder}/move`는 `parent_id`(null이면 대분류)와 `position`을 받습니다. `position`은 **자기 자신을 뺀** 형제 목록의 0-based 자리입니다. 형제 수를 넘기면 거절하지 않고 끝으로 접습니다. 화면이 센 자리와 서버가 아는 형제 수는 어긋날 수 있습니다.
- 이동할 때마다 형제 전체의 `sort_order`를 0부터 다시 매기고, 떠나온 자리도 함께 채웁니다. 번호를 벌려 두지 않습니다.
- 같은 상위 아래에서 이름은 겹칠 수 없습니다. 최상위는 `parent_id`가 NULL이라 유니크가 걸리지 않으므로 `parent_key` 생성 컬럼으로 0에 접어서 비교합니다.
- 하위 폴더가 있는 폴더는 소분류가 될 수 없습니다. 손자가 3단이 됩니다. 하위 폴더나 Q&A가 남아 있으면 삭제도 막습니다. 삭제 판정은 소프트 삭제된 Q&A까지 셉니다.
- Q&A의 `category`는 폴더 이름의 사본입니다. `folder_id`가 정본이며 이름을 바꾸면 사본도 따라갑니다.

## 병원 챗봇 상담 API

- 병원 `POST /api/v1/hospital/hospital-chatbot/consultations`는 운영시간을 검증한 뒤 `WAITING` 상담을 만들고 `{availability, consultation}`을 반환합니다. 이미 활성 상담이 있으면 운영시간과 무관하게 그 상담을 그대로 돌려줍니다. 진행 중인 상담은 마감 뒤에도 이어갈 수 있어야 합니다.
- 연타와 다중 탭은 한 건으로 수렴합니다. 사전 조회로 한 번, `active_hospital_id` 유니크 위반으로 또 한 번 막습니다. 병원당 활성 상담은 DB 레벨에서 1건입니다.
- 요청 본문의 `context_qna_id`(선택)는 상담을 부르기 직전에 보던 Q&A입니다. 서버가 **요청 시점의 질문·답변 본문을 복사**해 둡니다. Q&A가 뒤에 고쳐지거나 지워져도 상담원이 보는 것은 요청자가 읽던 글입니다. 없는 id를 보내도 상담은 그대로 열립니다.
- 운영시간 밖이면 422 `CONSULTATION_UNAVAILABLE`이며 `details.availability`에 판정 결과가 들어갑니다.
- 병원 `GET /consultations/active`는 생성과 **같은 `{availability, consultation}` 봉투**를 반환하며 활성 상담이 없으면 `consultation`이 null입니다. 새로고침 복구용이고, 함께 오는 `availability`로 연결 버튼 상태를 맞춥니다.
- 병원 `POST /consultations/{consultation}/cancel`은 대기 중에만, `/end`는 연결된 뒤에만 가능합니다. 순서가 어긋나면 409입니다. 다른 병원의 상담은 404입니다.
- 직원 `GET /api/v1/staff/hospital-chatbot/consultations`는 `status`(CSV), `assigned_staff_id`, `hospital_id`, `consultation_id`, `requester`, `from`, `to`, `sort`, `per_page`를 받습니다. `status`를 주지 않으면 대기·진행만 반환합니다.
- `from`·`to`는 `Y-m-d`이며 서버가 그날 처음과 끝으로 넓혀 `requested_at`에 겁니다. `to`를 시각 없이 그대로 쓰면 마지막 날이 통째로 빠지기 때문입니다.
- `requester`는 요청 당시 표시명 부분 일치입니다. 계정을 지워도 기록은 남아야 하므로 스냅샷을 봅니다. LIKE 와일드카드는 이스케이프해 글자 그대로 찾습니다.
- `sort`는 `queue`(기본)와 `recent`입니다. 대기 목록은 집어야 할 순서(대기 먼저, 오래 기다린 순)고 기록 조회는 최신순입니다. 같은 데이터를 다른 순서로 보는 두 화면이라 클라이언트가 정합니다.
- 직원 `POST /consultations/{consultation}/claim`은 조건부 UPDATE로 한 명만 통과시킵니다. 이미 다른 상담원이 가져갔으면 409 `CONSULTATION_ALREADY_CLAIMED`입니다. **같은 상담원이 다시 눌러도 성공으로 취급합니다.**
- 직원 `POST /consultations/{consultation}/end`는 담당 상담원만 가능합니다. 담당 검사는 조회가 아니라 UPDATE 조건에서 합니다. 담당이 아니면 403입니다.
- 직원 `GET /consultations/{consultation}`은 목록 항목에 `status_histories`를 더해 반환합니다. 이력은 `from_status`, `to_status`, `actor_type`, `actor_id`, `reason`, `occurred_at`이며 `from_status`가 null이면 상담이 생긴 순간입니다.
- 상담 DTO의 `waiting_seconds`는 대기 중이면 지금까지, 취소면 취소 시각까지, 나머지는 연결 시각까지입니다. `duration_seconds`는 연결→종료이며 **연결되지 않은 상담은 null**입니다. 0초로 내보내면 바로 끝난 상담과 구분되지 않습니다.
- `close_reason`은 `AGENT_COMPLETED`, `USER_CANCELED`, `USER_ENDED`, `WAITING_TIMEOUT`입니다. 대기 상담은 `hospital-chatbot:expire-waiting-consultations`가 설정된 시간(기본 30분) 뒤 자동 취소합니다.

## 병원 챗봇 상담 메시지 API

- 병원·직원 모두 `GET /consultations/{consultation}/messages`와 `POST`를 사용합니다. 경로 접두사만 다르고 계약은 같습니다.
- 목록은 `after_sequence`(0 이상)를 주면 그 뒤만, 주지 않으면 **최신 꼬리**를 반환합니다. meta에 `last_sequence`와 `has_older`가 들어갑니다. 재접속 후 순번 갭을 메울 때 이 파라미터를 씁니다.
- 전송 본문은 `content`(1~2000자)와 `client_message_id`(선택, 64자)입니다. 같은 `client_message_id`로 다시 보내면 새로 만들지 않고 먼저 저장된 메시지를 돌려줍니다. 재시도가 중복을 만들지 않습니다.
- `sequence`는 상담 안에서 1부터 증가하며 `lockForUpdate` 아래에서 매깁니다. 클라이언트는 이 값으로 정렬·중복 제거·갭 감지를 합니다.
- 전송은 `throttle:hospital-chatbot-message`(계정당 분당 120건)로 제한합니다. 연결된 상담에서만, 병원은 자기 상담에서만, 직원은 담당 상담에서만 보낼 수 있습니다.

## 병원 챗봇 상담 기록 내보내기 API

- 직원 `GET /consultations/export`는 목록과 **같은 필터**를 받아 CSV를 스트림합니다. 화면에서 본 것과 받은 파일이 달라지면 안 되므로 파싱과 검증을 목록과 공유합니다.
- 라우트는 `/{consultation}`보다 먼저 선언합니다. 뒤에 두면 `export`가 상담 ID로 잡힙니다.
- `from`·`to`는 **필수**입니다. 전체 기간은 동기 처리로 감당되지 않습니다. 기간은 `hospital_chatbot.export.max_days`(기본 92), 행 수는 `max_rows`(기본 10000)까지이며 넘으면 422와 함께 몇 건인지 알려 줍니다.
- 응답은 `text/csv; charset=UTF-8`이고 UTF-8 BOM으로 시작합니다. BOM이 없으면 엑셀이 한국어를 깨뜨립니다. `fputcsv`의 escape는 비워 RFC 4180대로 씁니다. PHP 기본 백슬래시 escape는 표준이 아닙니다.
- 열은 상담 ID, 병원, 요청자, 담당 상담원, 요청·연결·종료 시각, 대기 시간(초), 상담 시간(초), 상태, 종료 사유, 상담 전 질문, 요청 화면입니다. 상태와 사유는 코드가 아니라 한국어입니다. 파일은 화면 없이 혼자 읽힙니다.
- 스트림이 시작된 뒤의 예외는 되돌릴 수 없으므로, 로그를 남기고 파일 끝에 잘렸다는 행을 붙입니다.

## 병원 챗봇 실시간

- 채널은 [routes/channels.php](../routes/channels.php)에 있습니다. `hospital-chatbot.consultation.{consultationId}`는 상담 한 건, `hospital-chatbot.queue`는 상담원 대기 목록입니다.
- 기본 `withBroadcasting`은 user actor 전용이라 직원·병원은 각자의 `/api/v1/{staff|hospital}/broadcasting/auth`를 씁니다. 클라이언트는 Echo의 custom authorizer로 그 경로를 지정하고 쿠키·CSRF를 함께 보냅니다.
- 상담 채널은 그 상담의 병원 계정과 **모든 직원**이 구독합니다. 상담원은 담당이 아니어도 목록에서 상세로 들어가 볼 수 있어야 합니다. 대기 목록 채널은 직원만입니다.
- 이벤트는 `.hospital-chatbot.message.created`(상담 채널), `.hospital-chatbot.consultation.changed`(상담 채널), `.hospital-chatbot.queue.changed`(목록 채널)입니다.
- 메시지 발행은 저장 커밋 뒤에 Job으로 나갑니다. `broadcast_pending` 표식과 발행을 한 트랜잭션에서 처리하므로 중간에 실패하면 표식이 남고 `hospital-chatbot:dispatch-pending-broadcasts`가 매분 다시 집어 갑니다.
- 상태 변경 발행은 재발행 장치가 없습니다. 실패해도 양쪽 화면이 주기적으로 다시 조회해 결국 맞춰집니다.
- **현재 `BROADCAST_CONNECTION=log`로 발행을 끄고 폴링으로 운영합니다.** 메시지 5초, 목록 10초 주기이며 폴링은 원래 발행 실패 대비 경로입니다. 발행이 꺼져 있으면 재발행 커맨드도 아무것도 집지 않습니다. 큐 워커가 없는 환경에서 같은 메시지를 매분 다시 밀어 넣으면 Redis만 부풀기 때문입니다.
- 클라이언트는 순번이 아는 값보다 2 이상 앞서면 붙이지 않고 `after_sequence`로 다시 조회합니다. 실시간과 재조회가 같은 메시지를 줘도 순번으로 한 번만 남깁니다.

## 병원 챗봇 로그와 지표

- 도메인 로그는 [HospitalChatBotLog](../app/Domains/HospitalChatBot/Support/HospitalChatBotLog.php)를 거칩니다. 메시지 문장 대신 `event` 키를 고정하고 나머지는 컨텍스트로 넘깁니다. 수집기에서 `event`로 묶으면 그대로 지표가 됩니다.
- 상담 요청·중복 반환·연결·충돌·종료, 메시지 저장 실패, 발행 실패와 재시도, Export 실패, 운영시간 계산 오류를 남깁니다. Claim 충돌은 성공과 다른 event입니다. 같은 이름으로 묶이면 셀 수 없습니다.
- `hospital-chatbot:report-metrics`가 매분 현재 상태를 남깁니다. 대기 수, 진행 수, 최장 대기 시간, 미발행 메시지 수와 그중 가장 오래된 것, 그리고 발행 사용 여부입니다. 발행이 꺼져 있으면 미발행 수는 줄지 않으므로 숫자만 보고 장애로 읽지 않습니다.
- 운영시간 계산이 깨지면 500이 아니라 `CONFIGURATION_UNAVAILABLE`로 닫습니다. 여기서 예외를 올리면 SDK가 Q&A조차 열지 못합니다.

## 병원 프로모션 API

Staff 접두사는 `/api/v1/staff/hospital-promotions`입니다. `GET /board`는 일반 ApiResponse data에 `{today,left,right}`를 반환합니다. 양쪽은 `current: [{slot,promotion,filtered_out}]` 3개와 `upcoming: {items,meta}`입니다. 예약 자체가 없으면 null/false, 필터에 제외된 현재 예약은 내용 없이 null/true입니다. `GET /`는 ENDED만 data 배열과 최상위 meta로 반환하고 종료일·ID 내림차순입니다. `q`는 제목·등록 직원 이름/닉네임·숫자 ID 검색, 목록의 `status`는 ACTIVE/INACTIVE CSV 또는 배열의 다중선택이며 기존 단일 값도 받습니다. 미선택은 전체, 여러 값은 OR 조건이고 저장 폼의 단일 status와 구분합니다. 날짜 필터는 게시 구간과 교집합입니다. 예정 목록은 시작일·슬롯·ID 오름차순이며 `left_page`/`right_page`는 독립적입니다. 모든 목록은 15개 고정입니다.

- `GET /{promotion}`, `POST /`, `PATCH /{promotion}`은 상세 DTO를 반환합니다. multipart `POST` + `_method=PATCH`도 지원합니다. 목록에는 content를 넣지 않고 상세/저장에만 넣습니다. PATCH 필드 생략은 유지하며 `expected_updated_at`은 필수입니다.
- `GET /{promotion}/operation-histories`는 공통 이력 DTO와 meta이며 `operation_histories_page`(기본 1), `operation_histories_per_page`(기본 10, 최대 50)를 사용합니다.
- `GET /availability?side=&slot=&start_date=&end_date=&exclude_id=`는 `{available,conflicts,has_more}`를 반환합니다. conflicts는 시작일·ID 순 최대 10개의 `{id,title,side,slot,start_date,end_date}`이며 초과 시 has_more=true입니다. 조회·생성·수정 중 하나의 권한이 필요하고 exclude_id 사용은 해당 건 수정 권한이 추가로 필요합니다. 조회 권한이 없으면 conflicts는 빈 배열, has_more=false로 정보를 제한합니다. 저장 중 겹침도 422 `details.errors.slot`과 동일한 `details.availability`를 반환합니다. 최종 저장 트랜잭션의 검사만 확정적입니다.
- `POST /editor-images`는 `image,promotion_id?`를 받고 `{url,path}`를 반환합니다. promotion_id가 없으면 생성, 있으면 해당 건 수정 권한이 필요합니다. `DELETE /editor-images`의 `paths`/`urls`는 JSON 배열이며 생략/빈 배열은 아무것도 삭제하지 않습니다. 정리 응답은 `{requested_count,deleted_count,deleted_paths}`이고 물리 삭제는 커밋 후 공통 정리 작업에서 수행합니다.
- 병원 인증 경로 `/api/v1/hospital/promotions`는 ACTIVE이면서 KST CURRENT인 데이터만 `{left:[],right:[]}`로 반환합니다. 슬롯 오름차순 최대 3개, 빈 슬롯 placeholder는 없습니다. 공개 DTO는 `id,title,side,slot,start_date,end_date,banner`만, `GET /{promotion}`에는 content만 추가합니다. 작성자·직원 메타데이터는 노출하지 않습니다. 비공개/예정/종료 상세는 404입니다.
- 병원 배너 클릭 시 `POST /{promotion}/click`으로 `click_count`를 원자적으로 증가시키고 `{counted:true}`를 반환합니다. 계정·프로모션당 분당 1회, 계정 전체 분당 30회 제한이며 초과 시 429입니다. 읽기 GET/슬라이드 노출/직원 미리보기는 세지 않고 편집 토큰·감사·운영 히스토리를 변경하지 않습니다. 아직 연결 전인 병원 배너 클라이언트는 기존 개발용 `/view` 대신 `/click`을 사용합니다.

권한·동시성·설치 migration은 [프로모션 도메인 규칙](domain-rules.md#병원-프로모션)을 확인하세요.

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

## 병원 계정 생성

현재 정책은 아이디(`nickname`)+비밀번호 로그인, 이메일 인증입니다. 인증 연락처로 휴대폰 번호나 PASS를 사용하지 않습니다.
`AccountHospital.email`과 `email_verified_at`이 인증된 계정 정보이며, `name`은 호환용 병원명입니다. 처리자 표시는 병원 관계의 이름을 사용합니다.

초대 발송 UI는 두 흐름에서 같습니다. 이메일을 입력/수정할 수 있고 입점신청의 applicant_email은 기본 제안값일 뿐 강제 수신처가 아닙니다.

| 흐름                 | 초대 전                                | 계정 생성 완료 시                            |
| -------------------- | -------------------------------------- | -------------------------------------------- |
| 관리자가 생성한 병원 | 병원 존재, 계정 미연결                 | 해당 병원에 계정 1:1 연결                    |
| 입점신청 병원        | 입점 승인, 아직 병원으로 전환되지 않음 | 병원·사업자 정보·초기 지갑 생성 후 계정 연결 |

입점 전환 시 신규 병원은 검수 `NOT_APPLIED`(미신청), 운영 `ACTIVE`로 시작합니다.
기본 의료진은 생성하지 않습니다. 사업자등록증은 이전하고 의사면허 파일은 입점신청에 남깁니다.
이메일 인증 번호를 광고 수신 전화번호로 전용하지 않으며, 전환 직후 비어 있을 수 있는 `ad_reception_phone_1`은 검수 신청 전 등록해야 합니다.

가입 순서:

1. 직원이 권한과 대상 상태를 확인하고 계정 생성 이메일을 발송합니다.
2. 사용자가 `GET /hospital/auth/account-invitations/{token}`으로 초대를 확인합니다.
3. 초대의 `/email-verifications`에 인증번호를 요청하고 `/email-verifications/{emailVerification}/verify`에서 검증합니다.
4. 검증 증표와 계정 입력값으로 초대를 완료합니다. 대상→초대→인증 기록 순서로 잠그고 유효성·중복·소유권을 재확인합니다.
5. 계정 연결, 필요한 병원·지갑 생성, 증표 소비, 초대 사용 처리를 함께 커밋합니다.

이 문서에서 `/hospital`, `/staff`로 시작하는 축약 경로 앞에는 `/api/v1`이 붙습니다. 세부 payload는 [Hospital 인증 Request](../app/Modules/Hospital/Http/Requests/Auth)를 확인합니다.

- 초대 token은 64자이며 DB에는 SHA-256 해시를 저장합니다. 메일 큐의 민감 payload도 보호합니다.
- 기본 초대 72시간, 이메일 코드 5분, 재발송 간격 60초, 오입력 최대 5회, 가입 증표 15분입니다. [초대 설정](../config/hospital_account_invitation.php)이 기준입니다.
- 인증 증표는 초대와 이메일에 묶이며 한 번만 소비합니다. 인증 성공 화면을 거쳤다는 이유만으로 서버 검증을 생략하지 않습니다.
- 초대 이력은 기본 5개, 최대 20개 페이지네이션입니다. 사용 가능/사용/폐기/만료는 저장 시점뿐 아니라 현재 시각과 사용·폐기 기록으로 판정합니다.
- 가입 후 별도의 이메일 등록 화면은 없습니다. 개발 계정도 Factory에서 인증 이메일이 있는 상태로 생성합니다.

## 병원 비밀번호 재설정

- 직원 발송: `POST /staff/hospitals/{hospital}/password-reset-link`. 병원 조회와 재설정 발송 권한이 필요합니다.
- 직원 모달은 인증 이메일을 기본 표시하되 `recipient_email`로 다른 수신처를 지정할 수 있습니다. 인증된 계정 이메일 자체를 변경하지 않습니다.
- 다른 주소로 재설정 링크를 보낼 수 있는 권한은 계정 접근권을 넘길 수 있는 민감 권한입니다. 실제 수신처와 발송 직원을 기록합니다. 이전 기록의 수신처를 현재 이메일로 추정해 채우지 않습니다.
- 공개 찾기: `POST /hospital/auth/password-reset-link`. 가입 여부를 노출하지 않는 공통 응답을 사용하고 메일 처리는 큐에서 수행합니다.
- `POST /hospital/auth/password-reset/verify`는 병원명·마스킹 아이디와 링크 유효성을 반환합니다.
- `POST /hospital/auth/password-reset`는 계정→토큰 잠금 순서로 비밀번호 변경과 토큰 소비를 처리합니다. 기본 링크 수명은 60분입니다.
- 계정·이메일·비밀번호 상태 변경을 재검증하고 이전 링크 재사용을 막습니다. 비밀번호 변경 후 기존 세션/토큰의 인증도 무효화합니다.
- 임의 수신처를 지정하는 공통 발송 Action은 Staff 인가 없이 호출할 수 없도록 유지합니다.

병원 인증 메일은 [HospitalAccountMail](../app/Domains/AccountHospital/Support/HospitalAccountMail.php), [메일 템플릿](../resources/views/mail)을 사용합니다.
휴대폰 인증에서 전환하는 기존 환경의 데이터·링크 폐기 및 rollback 제한은 [운영 절차](operations.md)의 이메일 인증 전환 항목을 먼저 확인하세요.
