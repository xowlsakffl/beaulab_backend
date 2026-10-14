# 병의원관리자 챗봇·상담 채팅 개발 작업지시서

## 1. 목적과 적용 범위

병의원관리자가 등록된 Q&A를 확인하고, 필요한 경우 내부 직원에게 실시간 텍스트 상담을 요청할 수 있도록 한다.

- 문의자: 기존 병의원관리자 계정.
- 상담원: 기존 내부 직원 계정.
- 병의원관리자와 내부관리자 모두 로그인이 필수다. 기존 로그인 화면·API·웹 세션을 연동하며 비로그인 상담은 제공하지 않는다.
- 챗봇: 운영자가 등록한 질문·답변을 제공하는 방식. 생성형 AI 답변은 이번 범위에 포함하지 않는다.
- 적용 화면: `hospital-web`, `staff-web`. 일반 사용자 웹·앱과 일반 사용자 간 채팅은 대상이 아니다.
- 병의원관리자 웹에서는 Host Application과 독립적으로 빌드·배포·버전 관리되는 SDK를 로드한다. 네이티브 앱·웹뷰 컨테이너 개발은 포함하지 않는다.
- 초기에는 기존 서버 환경을 활용한다. 별도 서버 구매는 필수 조건이 아니며 실시간 프로세스와 전용 작업자는 나중에 별도 배포할 수 있어야 한다.

이 문서의 기능·개발 기준을 외주 범위로 협의한다. '확정 필요' 정책은 착수 전에 합의하며, 신규 배치안을 기존 구현으로 간주하지 않는다.

## 2. 영역별 작업과 책임

| 영역              | 외주 개발 범위                                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------- |
| 병의원관리자 프론트 | 기존 로그인 화면·세션 연동, 독립 SDK 로드, Q&A, 상담 요청, 대기·상담·종료 표시, 메시지 송수신, 현재 활성 상담 복구 |
| 내부관리자 프론트 | Q&A·운영시간 관리, 상담 대기·진행·종료 목록, 상담 연결·종료, 메시지 송수신, 기록 조회·검색                                |
| 백엔드            | 기존 로그인 인증 연동, 병의원별 데이터 격리, Q&A·상담·메시지 저장, 상담 상태 전이, 실시간 전달·복구                         |
| 연동·납품         | 양쪽 화면 연동, 개발 환경 설치, 배포·운영 절차, 전체 소스 및 SDK 빌드·사용 방법                                           |

우리 측은 저장소 접근, 기존 규칙·참고 코드, 격리된 개발 환경과 테스트 계정, 정책 결정, 코드 검토 및 운영 배포 승인을 담당한다. SDK 파일만 전달하는 것으로 연동 완료를 인정하지 않는다.

직원별 기능 권한 설계·등록, Policy/Gate·권한 미들웨어 추가, 메뉴·버튼의 권한 분기는 외주 범위에서 제외하고 내부 담당자가 구현한다. 외주사는 임의의 역할·권한명·권한표를 추가하거나 기존 권한 검사를 제거하지 않는다. 내부 권한 작업은 운영 공개 전 완료한다. 로그인 인증, 병의원별 데이터 격리, 상담 상태·담당자 일관성 검증은 이번 외주 범위에 포함한다.

파일·이미지 첨부, 음성·영상 상담, 앱 푸시·이메일·SMS 알림, 읽음·타이핑 표시, 자동 배정·상담 이관, AI 검색·답변은 별도 합의 없이는 포함하지 않는다. 기능 범위 변경은 일정·비용과 함께 합의한다.

## 3. 화면과 기본 동작

### 병의원관리자

- 병의원관리자 로그인 화면과 기존 로그인 API를 연결하고, 인증된 상태에서만 채팅 진입·Q&A·상담 기록을 제공한다. 현재 구현된 로그인 기능을 활용하며 챗봇 전용 계정이나 별도 로그인 체계를 새로 만들지 않는다.
- 비로그인 상태에서는 로그인 화면으로 안내하고, 로그인 완료 후 상담 화면으로 복귀한다. 로그인 실패·세션 만료·로그아웃 흐름도 연동한다.
- 질문 메뉴를 선택하면 등록된 답변을 표시한다.
- 운영시간과 현재 상담 가능 여부를 구분해서 안내한다. 운영시간 안이라고 상담원이 접속 중이라고 판단하지 않는다.
- 상담 요청 후 대기 상태, 상담원이 연결한 후 상담 중 상태를 표시한다.
- 상담 중에는 질문 선택 메뉴를 숨기고, 종료 후 다시 표시한다.
- 전송 중·전송 실패·재시도 상태를 구분한다. 서버 저장 성공 전에는 완료로 확정하지 않는다.
- 창을 닫거나 페이지를 이동했다고 상담을 자동 종료하지 않는다. 재접속하면 서버 상태와 기록을 복원한다.
- 종료·취소된 상담 이력은 병의원 화면에서 조회하지 않는다. `새 채팅 시작` 버튼으로 Q&A 첫 화면으로 돌아가며, 터미널 상담/Q&A UI 표시는 기본 24시간 미활동 후 새로 시작한다. 활성 상담은 이 초기화 대상에서 제외한다.
- 로그아웃·세션 만료 시 연결과 화면 데이터를 정리한다.

### 내부관리자

- 대기·진행 중·종료된 상담을 구분하고 병의원·상담 ID·기간 등 합의된 조건으로 조회한다.
- 목록에서 상담을 선택하여 병의원 정보, 담당자, 상태, 메시지와 일시를 확인한다.
- 기존 직원 로그인 후 상담 연결, 텍스트 답변, 종료를 수행한다. 직원별 기능 권한 연결은 내부 담당자가 별도로 구현한다.
- Q&A 등록·수정·삭제와 운영시간 설정을 제공한다. Q&A 순서·공개 설정 및 운영시간 세부 형식은 착수 시 확정한다.
- Q&A 수정·삭제로 과거 대화에 표시된 답변 내용이 바뀌지 않도록, 대화에 기록하는 답변은 당시 내용을 보존한다.
- 기록의 '관리'는 조회·검색·CSV 내보내기를 MVP에 포함한다. 메시지 수정·물리 삭제는 포함하지 않는다.
- 빈 목록, 조회 실패, 로그인 만료, 연결 끊김을 서로 다른 상태로 표시한다. 기존 API 오류 처리는 유지한다.

## 4. 백엔드 구성 규칙

현재 백엔드는 Laravel 12 / PHP 8.3+, MySQL, Redis, Horizon, Reverb 기반이다. 정확한 버전은 잠금 파일을 따른다. PHP로 별도 관리자 화면을 만들지 않는다.

### 기존 구조와 신규 도메인

`app/Domains/Chat`은 일반 사용자 간 1:1 대화이며, 기존 채널도 `AccountUser`와 참여자를 기준으로 인가한다. 병의원·직원 ID를 사용자 ID처럼 넣거나 기존 인가를 완화하여 재사용하지 않는다.

도메인명은 `HospitalChatBot`으로 정한다. 병의원관리자 Q&A와 상담원 연결·대화 기록을 하나의 업무 범위로 관리한다. `HospitalSupport`보다 이번 기능의 목적이 명확하며, 상담원 채팅도 해당 도메인에 포함한다. 다음은 현재 존재하는 경로가 아니라 신규 배치안이다.

```text
app/Modules/Hospital/
  routes/api_hospital.php
  Http/Controllers/HospitalChatBot/
  Http/Requests/HospitalChatBot/
app/Modules/Staff/
  routes/
  Http/Controllers/HospitalChatBot/
  Http/Requests/HospitalChatBot/

app/Domains/HospitalChatBot/
  Actions/Hospital/
  Actions/Staff/
  Queries/Hospital/
  Queries/Staff/
  Dto/Hospital/
  Dto/Staff/
  Models/
  Events/
  Jobs/
  Support/
```

| 위치          | 책임                                                                                    |
| ------------- | --------------------------------------------------------------------------------------- |
| Module routes | Actor별 URL·기존 로그인 인증·요청 제한 연결                                             |
| Controller    | 검증된 입력과 Actor를 Action에 전달하고 기존 ApiResponse로 응답                         |
| Request       | 입력 형식·길이·허용값·페이지 크기 검증                                                  |
| Action        | 로그인 주체·병의원 소유 범위 확인, 업무 흐름, 현재 상태 재검증, 트랜잭션과 후속 처리 조합 |
| Query         | DB 조회·저장·잠금 조건 구현. 이 프로젝트에서 읽기 전용 계층은 아님                      |
| Model         | 관계·캐스트·상태 상수                                                                   |
| Dto           | 명시적인 응답 필드 구성. DTO 안에서 추가 쿼리 실행 금지                                 |
| Event·Job     | 실시간 이벤트와 비동기 전달·재시도                                                      |
| Support       | 해당 도메인 내부 보조 로직                                                              |

- 파일·클래스는 `HospitalChatBot...ForHospitalAction`, `...ForStaffQuery` 등 가까운 도메인 명명 방식을 따른다. 클래스명과 파일명을 일치시킨다.
- 기존 도메인에 있는 `Policies`는 직원 기능 권한 작업 시 내부 담당자가 추가·연결한다. 이번 외주 배치안에는 빈 Policy 폴더나 권한용 우회 코드를 만들지 않는다.
- 필요한 폴더만 만든다. 모든 기능에 불필요한 Service·Repository·인터페이스를 추가하지 않는다.
- 병의원 상담 규칙은 해당 도메인에 둔다. `app/Common`이나 `app/Domains/Common`에 옮기는 것으로 공통화하지 않는다.
- 병의원 상담 Q&A는 챗봇 도메인에서 관리한다. 콘텐츠 형식이 비슷하다는 이유만으로 다른 게시물과 합치지 않는다.
- 공통 OperationHistory와 인증·예외·미디어 기능은 필요할 때 기존 진입점을 활용한다. 상담 메시지와 운영 변경 이력은 구분한다.

### 데이터와 API 기준

- 상담, 메시지, Q&A, 운영시간 등 필요한 테이블과 관계·제약을 착수 설계에 제출한다. 상세 컬럼은 검토 후 확정한다.
- 병의원과 발신 계정·담당 직원의 참조를 명확히 구분한다. 서로 다른 계정 테이블의 숫자 ID를 유형 없이 저장하지 않는다.
- 상담 연결·종료는 서버의 상태 전이로 처리한다. 두 직원의 동시 연결은 트랜잭션과 잠금 또는 조건부 갱신으로 한 명만 성공하게 한다.
- 메시지 재시도는 동일한 클라이언트 메시지 ID를 사용한다. 상담·발신자 기준의 DB 고유 제약으로 중복을 방지하고, 같은 키로 다른 본문을 보내면 거부한다.
- 메시지와 필수 전달 대기 정보를 함께 저장하고 커밋 이후 실시간 전달한다. 큐 장애로 저장 성공을 실패로 바꾸지 않는다.
- 상담 목록은 제한된 페이지 크기, 메시지 이력은 안정적인 커서·정렬 기준을 사용한다. 프론트와 API 계약을 함께 정의한다.
- 목록에서 전체 메시지를 로드하지 않는다. 관계 사전 로딩·필요 집계·조회 조건에 맞는 인덱스를 검토한다.
- 새 테이블은 create 마이그레이션으로 추가한다. 개발 단계의 기존 컬럼 변경은 기존 생성 마이그레이션에 반영한다. 이미 적용된 DB는 별도 적용 계획을 합의하며 임의 초기화하지 않는다.

## 5. 프론트 구성 규칙

현재 Next.js / React / TypeScript, pnpm workspace 구조를 유지한다. SDK의 빌드·배포 형태는 제안 후 확정한다.

```text
apps/hospital-web/
  app/(protected)/...             채팅 진입·페이지 연결
  components/hospital-chatbot/   SDK 표시·병의원 상담 UI
  hooks/hospital-chatbot/        SDK 수명·상담 상태 연동
  lib/hospital-chatbot/          SDK 어댑터·타입·API·응답 변환

apps/staff-web/
  app/(admin)/(pages)/...         상담·Q&A 관리 페이지
  components/hospital-chatbot/   목록·상담창·폼·모달
  hooks/hospital-chatbot/        조회·구독·상담 작업
  lib/hospital-chatbot/          타입·검증·query·payload·API
```

신규 경로는 배치안이다. 최종 메뉴 위치·URL은 기존 라우팅에 맞춰 협의한다. 신규 메뉴의 직원 권한 매핑은 내부 담당자가 처리한다.

- `page.tsx`는 라우트·metadata·화면 연결에 집중하고 Client에서 화면 흐름을 조합한다.
- 순수 함수·타입·응답 변환은 도메인 `lib`, React 상태와 수명은 `hooks`, 섹션 UI는 `components`에 둔다.
- 병의원 상담 endpoint·권한·상태를 아는 코드는 해당 모듈에 둔다. 개별 모듈 코드를 `common`이나 공용 패키지에 몰아넣지 않는다.
- 앱 간 직접 import, 패키지에서 앱 import, `packages/*/src` 우회 import를 금지한다.
- 공용 기능은 `@beaulab/api-client/web`, `@beaulab/auth`, `@beaulab/types`, `@beaulab/ui-admin`의 공개 export를 사용한다.
- staff의 `components/common`, `hooks/common`, `lib/common`은 직원 앱 내부 공통이다. hospital-web에서 직접 가져오지 않는다.
- UI 패키지에 상담 API·병의원 ID·권한명 같은 업무 의존성을 넣지 않는다. 양쪽이 실제 공유하는 채팅 코드가 있다면 도메인 전용 패키지 신설을 협의할 수 있으나, SDK 하나를 설치하기 위해 무조건 패키지를 만들지 않는다.
- HTTP는 각 앱의 기존 wrapper를 사용한다. staff는 `api`, hospital은 `hospitalApi`를 사용하며 SDK에도 이 요청 경로를 연결할 수 있어야 한다.
- 로그인 복구·CSRF·만료 처리를 SDK가 별도로 중복 구현하지 않는다. SDK 구조상 별도 인증이 필요하면 안전한 교환 방식과 책임을 먼저 협의한다.
- 관리자 폼·테이블·모달·날짜·상태·히스토리는 기존 UI를 재사용한다. 독립된 관리자 디자인 시스템을 도입하지 않는다.
- 목록·설정 화면은 기존 URL 복원·조회 취소·캐시 무효화 방식을 따른다. 실시간 메시지 목록을 일반 페이지 목록에 억지로 맞추지 않는다.

### SDK 필수 연동 계약

- 초기화, 열기·닫기, 종료·자원 해제, 오류·상태 이벤트와 설정 항목을 문서화한다. 구체적인 메서드명은 외주사가 제안한다.
- API·실시간 주소는 설정으로 주입한다. 외주사 도메인·개발 주소를 하드코딩하지 않는다.
- Next 서버 렌더링 중 `window`·DOM에 접근하지 않고, 브라우저에서 필요한 시점에 실행한다.
- 페이지 재진입으로 중복 연결·중복 이벤트가 생기지 않아야 한다. 언마운트·로그아웃 때 리스너·타이머·연결을 해제한다.
- SDK 전역 CSS가 관리자 폼·버튼을 변경하지 않도록 스타일을 격리한다.
- 새로고침·재접속 시 서버 기록으로 복구하고 메시지 ID로 중복 표시를 방지한다.
- SDK 원본 소스·타입·재현 가능한 빌드 절차를 납품한다. 외주사 CDN이나 비공개 서버에 대한 필수 의존은 사전 승인 없이는 추가하지 않는다.

## 6. 로그인 인증·데이터 격리·운영 안정성

- 병의원 웹과 직원 웹은 기존 웹 세션을 유지한다. 별도 로그인 저장소나 브라우저 장기 토큰을 도입하지 않는다.
- 병의원 ID는 인증 계정에서 결정한다. 요청의 병의원 ID나 SDK 초기화 값만으로 접근을 허용하지 않는다.
- 신규 직원 기능 권한은 외주사가 구현하지 않는다. 내부 담당자가 별도로 적용할 수 있도록 조회·연결·전송·종료·설정 변경의 Action과 endpoint를 명확히 구분한다.
- REST API뿐 아니라 실시간 채널 구독에도 Actor와 대상 접근을 검증한다. 기존 사용자용 채널을 병의원·직원에게 그대로 개방하지 않는다.
- 세션 만료·로그아웃 후 기존 연결로 계속 수신하지 않도록 서버 측 연결 종료 또는 인증 재확인 정책을 정의한다. 직원 권한 회수에 따른 처리는 내부 권한 작업에서 연결한다.
- 메시지 길이, 전송·상담 요청·재접속 빈도를 제한하고 연결 복구에는 간격 증가와 무작위 지연을 적용한다.
- 메시지는 안전한 텍스트로 렌더링한다. Q&A에서 HTML을 허용하면 기존 정제 규칙을 적용한다.
- 로그에는 토큰·쿠키·대화 본문 전체를 남기지 않는다. 상담·메시지·요청 ID, 오류 종류, 처리 결과로 추적한다.
- 기존 Redis/Horizon/Reverb를 우선 검토하되 SDK 호환성을 확인한다. 변경 라이브러리와 외부 서비스를 먼저 승인받는다.
- 병의원 상담 전용 큐·작업자를 구분한다. 실시간 서버와 작업자는 독립 실행·재시작·별도 배포 가능해야 한다.
- 같은 장비의 CPU·메모리, 공유 DB·Redis 부하는 여전히 공유된다. 프로세스 분리만으로 완전한 장애 격리를 보장한다고 보고하지 않는다.
- 실시간 서버 중단 시 일반 관리자 기능은 계속 동작하고, 저장된 메시지는 재연결 후 조회할 수 있어야 한다.
- 큐 지연·실패 건수, 연결 수, 저장·전달 지연을 확인할 방법과 복구 절차를 제공한다. 합의된 동시 접속·메시지 빈도로 부하를 검증하고 수치를 보고한다.

## 7. 확정된 MVP 정책과 잔여 확인 사항

| 항목 | 확정 정책 / 확인할 사항 |
|---|---|
| 상담 소유 | **병의원 기준. 병의원당 활성 상담 1건, 종료 후 새 세션 생성** |
| 상담 생성 | 창 열기로는 생성하지 않고 상담원 연결 요청 시 생성 |
| 대기 정책 | **취소 허용, 메시지 입력 비활성, 기본 30분 후 자동 만료** |
| 상담원 | **수동 Claim, 담당자만 답변·종료, 상담원별 동시 상담 수 제한 없음** |
| Q&A | **전체 병의원 공통, 24시간 이용 가능** |
| 운영시간 | **`Asia/Seoul`, 요일별 단일 구간, 날짜별 공휴일·임시휴무 강제 OFF, 운영 외에는 Q&A만 허용** |
| 기록 | **모든 상담은 서버에 저장. 종료 이력은 관리자만 조회. CSV 내보내기 MVP 포함** |
| 새 채팅 | **버튼 제공. 터미널 상담/Q&A UI는 기본 24시간 미활동 후 초기화. 활성 상담은 제외** |
| SDK | **Host와 독립적으로 빌드·배포·버전 관리. 기존 웹 세션은 Adapter로 재사용** |
| 성능 | **병의원 사용자 DAU 50 미만. 기존 Laravel·MySQL·Redis·Horizon·Reverb로 시작** |
| 잔여 확인 | 기록 보존기간, CSV 최대 기간·건수, 퇴점 병의원 정책, Q&A 중복 처리, SDK 인증 Adapter 세부 계약 |

잔여 미확정 정책을 개발자가 임의로 확정하거나 현재 서비스 정책으로 간주하지 않는다.

## 8. 진행·납품·완료 기준

1. 구조 제안: DB·API·상태 전이, 병의원·직원 로그인 연동, SDK 및 실시간 서버 구성, 변경 경로·의존성 제출. 신규 직원 권한표는 제출 대상이 아니다.
2. 첫 PR: Q&A 조회·등록을 기존 관리자 UI부터 API·DB까지 연결하여 구조 검토.
3. 상담 구현: 요청·연결·메시지·종료·기록을 기능 단위 PR로 개발.
4. 장애·인증 검증: 재연결, 중복 요청, 동시 연결, 비로그인 접근 차단·병의원별 데이터 격리, 큐 장애를 검증.
5. 인수: 개발 환경에서 실제 연동 확인, 전체 소스·설정·설치·배포·복구 절차와 미해결 항목 전달.

병의원 챗봇의 최종 통합 기준 브랜치는 백엔드·프론트 각 저장소의 `feat/hospital-chatbot`으로 고정한다. 첫 기능 브랜치는 최신 통합 기준 브랜치에서 생성하고, 이후 기능 브랜치는 직전 기능 브랜치에서 순차 분기한다. PR도 직전 기능 브랜치를 base로 열어 작업 간 의존성과 diff를 유지하며, 앞 PR이 통합되면 base를 `feat/hospital-chatbot`으로 변경한다. 구현·검증·PR 설명이 완료된 작업은 Ready PR로 생성하고, 실제 미완료 작업이 남은 경우에만 Draft를 사용한다. 커밋은 하나의 작업 목적만 담고, 코드·해당 테스트·필수 계약 문서를 같은 기능 단위로 완결한다. 기존 미커밋 작업·운영 설정을 덮어쓰지 않는다. 상용 서비스 비용, 소스 수정·운영 권한, 하자보수 기간과 책임은 별도 계약에 명시한다.

기능·코드·DB·설정 변경 PR 본문에는 `Test plan`을 필수로 포함한다. 자동 검사 명령과 결과, 로컬 수동 확인 절차와 기대 결과, 실행하지 못한 항목과 이유, DB·배포 영향 및 적용·복구 확인 항목을 구분해 기록한다. 문서만 변경하는 PR은 간단한 문서 검증 결과로 대신할 수 있으며, 검증하지 않은 항목을 통과로 표시하지 않는다.

별도 병의원 챗봇 전용·공용 개발 서버는 제공되지 않는다고 전제하되, 로컬 격리 서버와 로컬 전용 DB·Redis·큐·Reverb는 구현·검증에 사용할 수 있다. 챗봇과 직접 관련 없는 기능·공용 설정·기존 데이터는 변경하지 않는다. 운영·공유 환경 배포, 원격 DB 접속·마이그레이션·시딩, 실제 외부 발송, 원격 큐·스케줄러·Reverb 실행은 별도 승인·적용 절차 없이 수행하지 않는다. 실제 환경 반영이 필요한 항목은 실행하지 않고 적용 명령·순서·복구 방법만 인수 문서에 남긴다.

### 필수 검수 시나리오

- 병의원 A가 기존 로그인 화면에서 로그인한 후 질문을 조회하고 상담을 요청하면 로그인한 직원이 연결·답변·종료할 수 있다.
- 비로그인 요청과 만료된 세션은 상담 API·실시간 채널에 접근하지 못한다.
- 병의원 B는 A의 목록·메시지·채널에 접근하지 못한다. 직원별 기능 권한 검수는 내부 담당자의 별도 작업 범위다.
- 두 직원이 동시에 연결해도 담당자는 한 명으로 확정된다.
- 전송 응답 유실 후 재시도해도 메시지는 한 건이며, 재접속 후 누락·중복 표시 없이 복구된다.
- 실시간 서버·큐가 중단되어도 저장된 메시지가 사라지거나 일반 관리자 기능이 중단되지 않는다.
- 종료와 전송이 동시에 발생해도 합의한 상태 규칙을 일관되게 적용한다.
- 운영시간 외·빈 목록·조회 오류·세션 만료·반복 화면 진입을 확인한다.
- 병의원 웹 모바일 폭과 직원 관리자 화면에서 입력·스크롤·모달·SDK 스타일 충돌을 확인한다.

변경 앱 lint·타입 검사, 영향 범위 빌드, PHP 구문·Pint 검사를 수행한다. 기존 테스트는 격리 환경을 확인한 뒤 실행하며 새 자동화 테스트 범위는 착수 시 협의한다. 실제 운영 DB·공유 개발 DB 초기화, 전체 Seeder 실행, 외부 발송을 검증 수단으로 사용하지 않는다.

## 9. DB·환경변수·접속정보 전달

### 제공할 환경

개발자는 전용 로컬 MySQL·Redis 또는 승인된 외주 개발 환경을 사용한다. 기존 운영 `.env`와 실제 회원·상담 데이터가 포함된 DB 덤프를 전달하지 않는다. 아래 주소·DB명은 로컬 구성 예시이며 실제 발급된 공용 서버 정보가 아니다.

| 항목           | 기준 / 제공 내용                                                                                                          |
| -------------- | ------------------------------------------------------------------------------------------------------------------------- |
| DB             | MySQL 8 계열, 기본 문자셋 `utf8mb4`, 기본 collation `utf8mb4_unicode_ci`                                                  |
| DB 스키마      | [database/migrations](../database/migrations)와 Model이 기준, [schema.dbml](schema.dbml)은 탐색용                         |
| 기존 계정 관계 | `account_hospitals.hospital_id` → `hospitals.id`, 병의원과 계정 1:1. 직원은 `account_staffs` 모델의 실제 테이블 매핑을 확인 |
| 기존 채팅      | 일반 사용자 간 `Chat` 모델·관련 테이블은 참고용. 병의원 상담 데이터와 분리                                                  |
| Redis          | 세션·큐·캐시용. 개발자별 전용 인스턴스 또는 승인된 DB 번호·접두사 사용                                                    |
| 로컬 주소      | API `http://localhost:8000`, 직원 `http://localhost:3000`, 병의원 `http://localhost:3002`                                   |
| 실시간 주소    | Reverb 사용 시 로컬 예시 `ws://localhost:8080`, 공용 환경에서는 승인된 WSS 주소                                           |
| 개발 데이터    | [로컬 설치](local-setup.md), [개발 데이터](development-data.md), [.env.frontend.example](../.env.frontend.example) 참고   |

위 포트는 각 앱의 `dev` 스크립트에 지정된 기본값이다. 개발자 PC에서 해당 포트가 이미 사용 중이면 다른 포트로 실행하고, 그 Origin을 `.env`의 `STAFF_WEB_ORIGINS`·`HOSPITAL_WEB_ORIGINS`와 재설정 URL에 함께 추가한다. 저장소의 기본 포트를 개인 환경에 맞춰 바꾸지 않는다.

계정·병의원·권한 등 기존 FK 대상을 임의 복제하지 않는다. 신규 상담 테이블의 상세 컬럼·관계·인덱스와 API 계약은 첫 구조 검토 때 제출한다. 실제 배포 DB는 마이그레이션 파일과 다를 수 있으므로 적용 전 대조한다.

### 백엔드 로컬 환경변수 예시

전체 기본값은 `.env.frontend.example`을 사용하고 아래 상담 연동 항목을 검토한다. 기존 `.env`를 덮어쓰지 않는다. DB 사용자·DB 및 비밀 값은 별도로 생성·발급해야 하며 아래 블록만으로 실행 환경이 완성되는 것은 아니다.

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beaulab_frontend_chatbot
DB_USERNAME=beaulab_frontend
DB_PASSWORD=

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=10
REDIS_CACHE_DB=11
REDIS_PREFIX=beaulab-chatbot-local:
CACHE_STORE=redis
CACHE_PREFIX=beaulab-chatbot-local-cache:
SESSION_DRIVER=redis
WEB_SESSION_CONNECTION=default
WEB_SESSION_REVOCATION_STORE=redis
QUEUE_CONNECTION=redis
HORIZON_PREFIX=beaulab-chatbot-local-horizon:

STAFF_WEB_ORIGINS=http://localhost:3000
HOSPITAL_WEB_ORIGINS=http://localhost:3002

# Reverb 사용 시 적용. 비밀 값은 로컬 전용으로 생성한다.
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=chatbot-local
REVERB_APP_KEY=chatbot-local-public
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_SCALING_ENABLED=false

MAIL_MAILER=array
SMS_ENABLED=false
PUSH_ENABLED=false
FCM_ENABLED=false
APNS_ENABLED=false
FRONTEND_SEED_ENABLED=false
FRONTEND_SEED_PASSWORD=
```

- `APP_KEY`는 신규 환경에서만 생성한다. 기존 키를 교체하지 않는다.
- MySQL 비밀번호·Reverb secret·시드 계정 비밀번호는 비어 있는 채로 사용하지 말고 로컬 전용 값으로 설정한다. 운영 값은 재사용하지 않는다.
- DB 10/11은 예시다. 기존 서비스가 사용하는지 확인하며 Redis 논리 DB·접두사는 보안 권한 격리를 대신하지 않는다.
- `REVERB_HOST/PORT`는 Laravel의 전송 대상, `REVERB_SERVER_HOST/PORT`는 서버 수신 주소다. 별도 배포·프록시 구성 시 구분한다.
- 현재 `config/reverb.php`의 `allowed_origins`는 `['*']`다. 이것을 제한 완료 상태로 간주하지 말고 공용 서비스 적용 전 승인된 Origin만 허용하도록 구성한다. Origin 제한은 채널 인가를 대신하지 않는다.
- 현재 `config/horizon.php`의 `chat` 큐는 일반 사용자 채팅용이다. 병의원 상담 전용 큐와 supervisor는 신규 구성 대상이며 환경변수 한 줄만으로 자동 생성되지 않는다.

### 프론트 환경변수와 SDK 설정

각 앱의 `.env.local`에 설정한다. 기존 요청은 같은 출처 `/api/v1/{actor}`와 Next rewrite를 사용한다.

```dotenv
# staff-web / hospital-web 공통: Next 서버에서 접근 가능한 Laravel 주소
API_URL=http://127.0.0.1:8000

# staff-web: 상대 미디어 주소 해석에 필요한 경우
NEXT_PUBLIC_API_URL=http://localhost:8000
```

SDK에는 API 요청 어댑터, 브라우저에서 접근 가능한 WebSocket 주소, 공개 app key 및 채널 인가 경로가 필요하다. 이 설정의 변수명은 아직 구현되어 있지 않으므로 외주사가 제안하고 실제 읽는 코드와 함께 추가한다. 백엔드의 내부 주소를 브라우저 접속 주소와 혼동하지 않는다.

`DB_PASSWORD`, `APP_KEY`, `REVERB_APP_SECRET`, Redis 비밀번호는 프론트·SDK·`NEXT_PUBLIC_*`에 넣지 않는다. 공개 app key만으로 인증이 되는 것이 아니며, 병의원·직원 채널은 기존 로그인으로 별도 인가해야 한다.

### 별도 보안 채널로 전달할 접속정보

- 공용 개발 환경 사용 여부와 담당자, API·직원·병의원·WSS 주소.
- 필요한 경우에 한해 SSH/VPN 접근과 개발 DB host·port·database·username·password·TLS 조건.
- 개발 Redis 접속정보, 할당 DB·접두사, 필요한 경우 전용 ACL 계정.
- 병의원 A·B와 내부 직원의 로그인 테스트 계정·비밀번호. 직원 권한별 테스트 계정은 내부 권한 작업에서 준비한다.
- Reverb 개발 전용 키·secret 및 배포 권한 범위.

이 항목은 아직 발급·전달되지 않았다. 실제 값은 접근 제한된 비밀 공유 수단으로 전달하며 Git·작업지시서·PR에 기록하지 않는다. 외주 종료 시 접근 회수와 공유 비밀 교체를 수행한다.

## 10. 참고 문서와 실제 코드

### 백엔드 저장소

- [구조와 개발 기준](architecture.md), [API·인증](api.md), [메시징](messaging.md), [운영 절차](operations.md), [Git 협업](git-workflow.md).
- Action·Query·DTO 구성: [NoticeCreateForStaffAction](../app/Domains/Notice/Actions/Staff/NoticeCreateForStaffAction.php), [NoticeCreateForStaffQuery](../app/Domains/Notice/Queries/Staff/NoticeCreateForStaffQuery.php).
- 저장 후 실시간 전달 참고: [ChatMessageSendForUserAction](../app/Domains/Chat/Actions/User/ChatMessageSendForUserAction.php), [BroadcastChatMessageJob](../app/Domains/Chat/Jobs/BroadcastChatMessageJob.php).
- Actor 경계: [병의원 API 라우트](../app/Modules/Hospital/routes/api_hospital.php), [현재 실시간 채널](../routes/channels.php).

참고 코드의 일반 사용자 계정·차단·신고 규칙을 병의원 상담에 복사하지 않는다.

### 프론트 저장소

- [구조 안내](https://github.com/beaulab/beaulab_new_frontend/blob/master/doc/architecture.md), [화면 개발 규칙](https://github.com/beaulab/beaulab_new_frontend/blob/master/doc/development.md), [API·인증](https://github.com/beaulab/beaulab_new_frontend/blob/master/doc/api.md), [Git 협업](https://github.com/beaulab/beaulab_new_frontend/blob/master/doc/git-workflow.md).
- 기존 API wrapper: `apps/staff-web/lib/common/api.ts`, `apps/hospital-web/lib/common/api.ts`.
- 공용 패키지 export: `packages/ui-admin/package.json`, `packages/api-client`, `packages/auth`, `packages/types`.
- 목록·폼 참고: `components/account-user`, `lib/account-user`, `components/notice`, `lib/notice`와 해당 페이지 Client.

기존 코드의 모든 패턴을 무조건 복제하라는 의미는 아니다. 실제 계약을 유지하면서 문제를 발견하면 근거와 대안을 제시하고 변경 범위를 합의한다.
