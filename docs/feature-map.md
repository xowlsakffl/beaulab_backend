# 기능 지도

메뉴는 탐색 목차이고 업무 규칙의 경계는 아닙니다. 아래 표는 직원 웹 메뉴와 백엔드 도메인을 연결합니다. 경로는 직원 웹 기준이며 상세·등록·수정 하위 경로는 각 라우트에서 확인합니다.

| 메뉴                          | 화면 경로                                                                            | 서버 도메인 / 기준서                                                                                        |
| ----------------------------- | ------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------- |
| 대시보드                      | `/hospital-dashboard/dashboard`                                                      | Common/Dashboard, [통계](business/statistics.md)                                                            |
| 병의원·의료진·입점신청        | `/hospital-manage/hospitals`, `doctors`, `hospital-entries`                          | Hospital, HospitalDoctor, HospitalEntry, AccountHospital; [병의원](business/hospitals.md)                   |
| 충전금 현황·내역·관리         | `/wallet-manage/deposits`, `history`, `hospitals`                                    | HospitalWallet; [충전금](business/wallet-pricing.md)                                                        |
| 이벤트 DB·비대면·리얼모델     | `/customer-db-manage/events`, `consultations`, `real-models`                         | HospitalEvent, RemoteConsultation; [상담신청](business/consultations.md)                                    |
| 이벤트·광고 관리·광고 현황    | `/ads-manage/events`, `event-ads`, `calendar`                                        | HospitalEvent, HospitalEventAd; [이벤트와 광고](business/events-advertising.md)                             |
| 영상 등록·동의 여부           | `/video-manage/videos`, `consents`                                                   | HospitalVideo; [콘텐츠](business/members-content.md)                                                        |
| 성형 후기·시술 후기·토크·평가 | `/post-manage/surgery-reviews`, `treatment-reviews`, `talks`, `hospital-evaluations` | HospitalReview, Talk, HospitalEvaluation; [콘텐츠](business/members-content.md)                             |
| 신고 게시물·채팅              | `/reported-post-manage/*`                                                            | Common/ContentReport, Chat; [신고](business/moderation.md)                                                  |
| 챗봇 통계·상담·Q&A·운영 관리  | `/hospital-chatbot/statistics`, `consultations`, `qnas`, `operating-hours`           | HospitalChatBot; [챗봇](business/chatbot.md)                                                                |
| 일반회원·회원 현황            | `/user-manage/users`, `statistics`                                                   | AccountUser, SiteVisit; [회원](business/members-content.md), [통계](business/statistics.md)                 |
| 공지사항                      | `/notice-manage/notices`                                                             | Notice; [운영 설정](business/operating-settings.md)                                                         |
| 병의원 프로모션               | `/promotion-manage/hospitals`                                                        | HospitalPromotion; [광고](business/events-advertising.md)                                                   |
| 배너·후기·영상·기타 노출      | `/content-manage/banners`, `reviews`, `videos`, `other-exposures`                    | BannerExposure, ReviewExposure, VideoExposure, EventExposure                                                |
| 이벤트 노출 위치              | `/admin-settings/event-display-positions`                                            | EventExposure; [광고](business/events-advertising.md)                                                       |
| 직원·역할 기본권한            | `/admin-settings/staff`                                                              | AccountStaff; [권한](business/staff-permissions.md)                                                         |
| 상담신청 단가·광고 비용       | `/admin-settings/db-pricing`, `ad-pricing`                                           | ConsultationPricing, HospitalEventAd; [단가](business/wallet-pricing.md)                                    |
| 카테고리·앱 메뉴              | `/category-hashtag-manage/categories`                                                | Common/Category; [카테고리](business/categories.md)                                                         |
| 금지어·자동 닉네임            | `/admin-settings/harmful-words`, `nicknames`                                         | Common/HarmfulWord, Nickname; [운영 설정](business/operating-settings.md)                                   |
| 내 정보·알림                  | `/admin-settings/profile`, `/notifications`                                          | AccountStaff, Common/Notification; [권한](business/staff-permissions.md), [알림](business/notifications.md) |

## 화면 밖의 실행 경로

- 병의원 API: 초대·인증·비밀번호, 소속 업무, 비대면 견적 답변, 챗봇 상담과 Q&A·이용 이벤트.
- 사용자 API: 공개 카테고리 탐색·노출 조회, 회원 인증, 후기·토크·평가 관련 처리, 이벤트·리얼모델 신청, 회원 채팅·차단·알림.
- 배치: 미디어 정리, 알림·SMS·실시간 재발행, 대기 상담 만료, 이용 이벤트 보관 기간 정리, 평점 집계, 지갑 무결성 점검.
- 내부 운영 도구: 별도 직원 도구 세션·IP·Gate로 보호된 API 문서, 큐·요청 진단 도구.

## 구현 경계를 구분해야 하는 부분

| 항목                 | 현재 확인되는 범위                                                                                              |
| -------------------- | --------------------------------------------------------------------------------------------------------------- |
| 비대면 신청          | 검수·배정·답변·차감은 연결됨. 사용자 제출용 공개 라우트 연결은 확인되지 않음                                    |
| 이벤트·리얼모델 과금 | 이벤트 신청 단가 스냅샷과 리얼모델 고정 단가 설정은 있음. 두 신청의 답변 시 차감 완료 흐름은 현재 연결되지 않음 |
| 닉네임 자동 생성     | 생성기·회원 생성 Action이 있음. 일반 가입 API 전체가 연결됐다는 의미는 아님                                     |
| 광고 비용 세부 가격  | 위치 기본값·예약 적용이 있음. 순번·카테고리별 세부 가격 편집은 현재 범위 아님                                   |
| 뷰티                 | 서버 도메인·일부 API·직원 메뉴 존재. 다수 웹 페이지는 제목만 표시                                               |
| FAQ·해시태그 등      | 잔존 코드·권한·경로가 있을 수 있음. 현재 메뉴에 없으므로 활성 서비스 기능으로 안내하지 않음                     |
| 알림 전체 유형       | 등록된 유형과 실제 발행 연결이 다름. 숨김·미연결 유형은 [알림](business/notifications.md)에 명시                |

## 코드 기준점

- [직원 라우트](../app/Modules/Staff/routes/api_staff.php), [병의원 라우트](../app/Modules/Hospital/routes/api_hospital.php), [사용자 라우트](../app/Modules/User/routes/api_user.php), [뷰티 라우트](../app/Modules/Beauty/routes/api_beauty.php)
- [콘솔 일정](../routes/console.php), [서버 도메인](../app/Domains)
- [직원 메뉴](https://github.com/beaulab/beaulab_new_frontend/blob/master/apps/staff-web/components/common/sidebar-menu.tsx)
- [화면 흐름](https://github.com/beaulab/beaulab_new_frontend/blob/master/docs/screen-flows.md)

라우트·메뉴·권한을 추가하거나 제거할 때 이 표와 해당 업무 문서를 함께 갱신합니다.
