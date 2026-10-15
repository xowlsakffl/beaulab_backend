# 개발 데이터

## 데이터 세트

| 구분                      | 용도                                     | 재실행 성격                      |
| ------------------------- | ---------------------------------------- | -------------------------------- |
| DatabaseSeeder 기본       | 권한·직원·분류·닉네임·단가·병의원 특징   | 시더별 정책이 다름               |
| DemoSeeder                | 전체 관리 화면·상태·통계의 합성 데이터   | 업무 데이터가 있는 DB에서는 거절 |
| FrontendDevelopmentSeeder | 작은 격리 환경의 인증·병의원·이벤트·채팅 | 전용 빈 DB에서 1회               |
| 도메인 개발 시더          | 특정 화면 시나리오                       | 각 시더 선행 조건·수정 범위 확인 |
| Factory                   | 테스트·샘플 모델 생성                    | 상태·관계를 호출자가 명시        |

기본 설정 시더와 개발 샘플을 동일한 '여러 번 실행해도 무조건 안전' 정책으로 보지 않습니다. AuthorizationSeeder는 역할 기본권한을 코드 정의로 동기화하므로 운영자가 조정한 템플릿을 보존하는 단순 추가 명령이 아닙니다.

## 전체 데모 실행 조건

`DEVELOPMENT_SEED_ENABLED=true`일 때 DatabaseSeeder가 데모를 호출합니다. 실제 DB 이름은 `DEVELOPMENT_SEED_DATABASE`와 일치해야 합니다.

Guard는 local/testing, 로컬 MySQL 호스트, 실제 접속 DB, local 공개·비공개 미디어 디스크, SMS/PUSH 비활성, log/array 메일을 검사합니다. 비어 있는 업무 테이블과 동시 실행 잠금도 확인합니다.

직원 이메일과 12자 이상 `SEED_STAFF_PASSWORD`를 준비합니다. 문서·저장소에 공용 비밀번호를 적지 않습니다. Faker 기본 시드는 20261015이며 `DEVELOPMENT_SEED_RANDOM_SEED`로 조정합니다.

## 호출 순서

기본: Authorization → AccountStaff → Category → NicknameWord → ConsultationPricing → HospitalFeature.

데모:

1. 입점신청 → 병의원 → 의료진 → 동영상 → 뷰티 → 전문가 → 일반회원.
2. 토크 → 후기 → 평가 → 이벤트 → 이벤트 DB → 리얼모델 → 지갑.
3. 비대면 → 신고 → 배너 → 광고 → 콘텐츠 노출 → 운영 설정.
4. 챗봇 Q&A → 상담 → 챗봇 통계 → 회원 통계 → 시나리오 보완.

[DatabaseSeeder](../../database/seeders/DatabaseSeeder.php), [DemoSeeder](../../database/seeders/DemoSeeder.php), [보완 시더](../../database/seeders/DevelopmentScenarioCompletionSeeder.php)가 실행 순서의 기준입니다.

## 확인할 시나리오

| 영역               | 목적                                                   |
| ------------------ | ------------------------------------------------------ |
| 병의원·의료진·입점 | 검수 단계, 운영 상태, 계정 미생성, 빈·부분 이미지      |
| 이벤트·광고·노출   | 성형·쁘띠·기획전, 이미지/텍스트, 예정·진행·종료·중지   |
| 고객 DB            | 신규·중복, 검수·배정·답변, 잔액 충분·부족              |
| 지갑               | 유상·서비스·예약, 소진·환불·지급·회수와 원장           |
| 콘텐츠·신고        | 공개·비공개, 댓글·투표·평점, 신고·경고·차단            |
| 챗봇               | 대기·진행·종료·취소, Q&A 문맥, 메시지·일별 이용 이벤트 |
| 설정·알림          | 단가 버전, 사전, 직원별 수신·읽음                      |
| 통계               | 최근 일별·월별·요일별 데이터와 실제 원본 관계          |

정확한 건수는 현재 시더와 분류 수에 따라 달라집니다. 고정 숫자를 별도 계약으로 복제하지 않습니다. 모든 화면의 모든 입력 조합·동시성·외부 전달을 시더만으로 검증하지 않습니다.

날짜는 실행일 기준입니다. Faker seed는 보안 토큰·자동 증가 ID·실제 무작위 배정까지 고정하지 않습니다. 오래된 데모의 기간이 종료됐다고 실제 업무 데이터를 일괄 갱신하지 않습니다.

## 소규모 환경

FrontendDevelopmentSeeder는 `APP_ENV=local`, `FRONTEND_SEED_ENABLED=true`, 12자 이상 FRONTEND_SEED_PASSWORD와 `beaulab_frontend_<name>` 형식의 빈 전용 DB를 요구합니다.

직원 frontend_admin·frontend_restricted, 사용자 frontend_alice·frontend_bob·빈내역·정지 계정, 병의원 2곳과 이벤트·채팅을 생성합니다. 로그인 필드는 계정 유형에 따라 ID 또는 이메일이며 출력되는 생성 목록으로 확인합니다. 전체 데모와 같은 DB에 섞지 않습니다.

## 파일·외부 부작용

샘플 미디어는 `public/test.png` 등을 local 디스크에 복사한 합성 자료입니다. 실제 의료 사진·YouTube 영상·PDF 재생 품질을 보증하지 않습니다. 테스트용 URL·연락처를 실제 발송 대상으로 사용하지 않습니다.

DB 트랜잭션 롤백이 생성 파일까지 지우지는 않습니다. 실패 시 생성 파일과 DB 참조를 대조해 범위를 정한 후 정리합니다. 실제 API·Model 이벤트·알림 전달을 우회하는 시더는 통합 테스트와 다릅니다.

## 초기화와 검증

초기화는 자동 재실행 절차가 아닙니다. 호스트·DB 확인 → 쓰기 작업자·스케줄러 통제 → DB·키·파일 백업과 복원 가능성 확인 → 승인된 범위만 초기화 → 마이그레이션·시딩 → 관계·원장·미디어·권한·통계 대조 순서입니다.

다른 DB, 기존 업로드 파일, APP_KEY를 함께 삭제하지 않습니다. 이전 DB를 참조하는 큐를 새 DB에 재생하지 않습니다.

- [시더 전체](../../database/seeders), [Factory 전체](../../database/factories)
- [실행 Guard](../../app/Common/Support/DevelopmentDataGuard.php), [설정](../../config/seeding.php)
- [로컬 실행](local-development.md), [운영 복구](runbook.md)

도메인별 단독 시더는 호출자를 제거했다고 불필요한 파일이 아닙니다. 기존 DB 보완용 수동 경로와 테스트 참조를 확인한 뒤 판단합니다.
