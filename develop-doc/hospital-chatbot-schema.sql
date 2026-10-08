-- 병의원 챗봇 테이블 9개 (MySQL 8.4)
--
-- 로컬 개발 DB에서 SHOW CREATE TABLE로 뜬 실제 DDL입니다. Laravel 마이그레이션이
-- 정본이므로 보통은 `php artisan migrate` 를 쓰면 됩니다. 이 파일은 스키마를
-- 눈으로 확인하거나 마이그레이션을 돌릴 수 없는 환경에서 참고하려고 둡니다.
--
-- 순서대로 실행해야 합니다. 뒤 테이블이 앞 테이블을 FK로 참조합니다.
-- 기존 테이블(hospitals, account_hospitals, account_staffs)이 먼저 있어야 합니다.
--
-- 마이그레이션 파일: database/migrations/2026_09_2*_*hospital_chatbot*.php
-- 구조 설명: develop-doc/architecture.md#병의원-챗봇-구조

CREATE TABLE `hospital_chatbot_qna_folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Q&A 폴더 ID',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '상위 폴더 ID. 없으면 대분류',
  `depth` tinyint unsigned NOT NULL COMMENT '1=대분류, 2=소분류',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '폴더 이름',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '같은 상위 안에서의 노출 순서',
  `created_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '등록한 직원 계정 ID',
  `updated_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '수정한 직원 계정 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_key` bigint unsigned GENERATED ALWAYS AS (ifnull(`parent_id`,0)) STORED COMMENT '유니크 비교용. 최상위는 0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hcqf_parent_name_unique` (`parent_key`,`name`),
  KEY `hcqf_parent_fk` (`parent_id`),
  KEY `hcqf_created_by_fk` (`created_by_staff_id`),
  KEY `hcqf_updated_by_fk` (`updated_by_staff_id`),
  KEY `hcqf_depth_sort_index` (`depth`,`sort_order`,`id`),
  CONSTRAINT `hcqf_created_by_fk` FOREIGN KEY (`created_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hcqf_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `hospital_chatbot_qna_folders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `hcqf_updated_by_fk` FOREIGN KEY (`updated_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 Q&A 폴더(2단)';

CREATE TABLE `hospital_chatbot_qnas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '병의원 챗봇 Q&A ID',
  `folder_id` bigint unsigned DEFAULT NULL COMMENT '속한 Q&A 폴더 ID',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Q&A 분류. folder_id가 정본이고 이 값은 사본이다',
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '병의원 화면에 노출할 질문',
  `question_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '공백·대소문자를 정규화한 질문 해시',
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '일반 텍스트 답변',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE' COMMENT '노출 상태(ACTIVE, INACTIVE)',
  `sort_order` int unsigned NOT NULL DEFAULT '0' COMMENT '전체 노출 순서',
  `created_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '등록한 직원 계정 ID',
  `updated_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '수정한 직원 계정 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '소프트 삭제 일시',
  `active_question_key` char(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`question_key`,NULL)) STORED COMMENT '활성 Q&A 질문 중복 방지용 생성 키',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hospital_chatbot_qnas_active_question_unique` (`active_question_key`),
  KEY `hospital_chatbot_qnas_created_by_staff_id_foreign` (`created_by_staff_id`),
  KEY `hospital_chatbot_qnas_updated_by_staff_id_foreign` (`updated_by_staff_id`),
  KEY `hospital_chatbot_qnas_status_sort_idx` (`status`,`sort_order`,`id`),
  KEY `hospital_chatbot_qnas_question_deleted_idx` (`question_key`,`deleted_at`),
  KEY `hospital_chatbot_qnas_category_status_sort_idx` (`category`,`status`,`sort_order`,`id`),
  KEY `hcq_folder_status_sort_index` (`folder_id`,`status`,`sort_order`,`id`),
  CONSTRAINT `hcq_folder_fk` FOREIGN KEY (`folder_id`) REFERENCES `hospital_chatbot_qna_folders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hospital_chatbot_qnas_created_by_staff_id_foreign` FOREIGN KEY (`created_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hospital_chatbot_qnas_updated_by_staff_id_foreign` FOREIGN KEY (`updated_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 관리자 챗봇 공통 Q&A';

CREATE TABLE `hospital_chatbot_operating_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 운영시간 ID',
  `day_of_week` tinyint unsigned NOT NULL COMMENT 'ISO 요일(월요일 1~일요일 7)',
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '상담 운영 여부',
  `opens_at` time DEFAULT NULL COMMENT '상담 시작 시각',
  `closes_at` time DEFAULT NULL COMMENT '상담 종료 시각',
  `updated_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '마지막 수정 직원 계정 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hospital_chatbot_operating_hours_day_of_week_unique` (`day_of_week`),
  KEY `hospital_chatbot_operating_hours_updated_by_staff_id_foreign` (`updated_by_staff_id`),
  CONSTRAINT `hospital_chatbot_operating_hours_updated_by_staff_id_foreign` FOREIGN KEY (`updated_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 요일별 상담 운영시간';

CREATE TABLE `hospital_chatbot_closure_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 휴무일 ID',
  `closure_date` date NOT NULL COMMENT '상담을 강제로 중단할 날짜',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '공휴일·임시휴무 사유',
  `updated_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '마지막 수정 직원 계정 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hospital_chatbot_closure_dates_closure_date_unique` (`closure_date`),
  KEY `hospital_chatbot_closure_dates_updated_by_staff_id_foreign` (`updated_by_staff_id`),
  CONSTRAINT `hospital_chatbot_closure_dates_updated_by_staff_id_foreign` FOREIGN KEY (`updated_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 공휴일·임시휴무 예외';

CREATE TABLE `hospital_chatbot_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '병의원 챗봇 공통 설정 ID',
  `is_force_closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '상담원 연결 강제 중단 여부',
  `force_closed_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '강제 중단 사유(내부 확인용)',
  `updated_by_staff_id` bigint unsigned DEFAULT NULL COMMENT '마지막 수정 직원 계정 ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hospital_chatbot_settings_updated_by_staff_id_foreign` (`updated_by_staff_id`),
  CONSTRAINT `hospital_chatbot_settings_updated_by_staff_id_foreign` FOREIGN KEY (`updated_by_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 공통 설정';

CREATE TABLE `hospital_chatbot_consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 ID',
  `hospital_id` bigint unsigned NOT NULL COMMENT '상담을 요청한 병의원 ID',
  `hospital_name_snapshot` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 당시 병의원 표시명',
  `requested_by_account_hospital_id` bigint unsigned DEFAULT NULL COMMENT '요청한 병의원 계정 ID. 감사 용도이며 상담 소유권을 결정하지 않는다',
  `requester_name_snapshot` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 당시 요청자 표시명',
  `context_qna_id` bigint unsigned DEFAULT NULL COMMENT '상담을 요청하기 직전에 보던 Q&A ID',
  `context_qna_question_snapshot` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 당시 질문 본문',
  `context_qna_answer_snapshot` text COLLATE utf8mb4_unicode_ci COMMENT '요청 당시 답변 본문',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '상담 상태(WAITING, CONNECTED, ENDED, CANCELED)',
  `active_hospital_id` bigint unsigned DEFAULT NULL COMMENT '활성 상담일 때만 채워지는 병의원 ID. 병의원당 활성 상담 1건 보장용',
  `assigned_staff_id` bigint unsigned DEFAULT NULL COMMENT '연결된 상담원 직원 계정 ID',
  `assigned_staff_name_snapshot` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '연결 당시 상담원 표시명',
  `requested_at` timestamp NOT NULL COMMENT '상담 요청 시각',
  `connected_at` timestamp NULL DEFAULT NULL COMMENT '상담원 연결 시각',
  `ended_at` timestamp NULL DEFAULT NULL COMMENT '상담 종료 시각',
  `canceled_at` timestamp NULL DEFAULT NULL COMMENT '상담 취소 시각',
  `close_reason` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '종료·취소 사유 코드',
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT '마지막 메시지 시각',
  `last_message_sequence` bigint unsigned NOT NULL DEFAULT '0' COMMENT '마지막 메시지 순번',
  `source_application` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 Host 정보',
  `source_page_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '요청 당시 화면 경로',
  `version` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Optimistic Lock 버전',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hcc_active_hospital_unique` (`active_hospital_id`),
  KEY `hcc_requester_fk` (`requested_by_account_hospital_id`),
  KEY `hcc_status_requested_at_index` (`status`,`requested_at`),
  KEY `hcc_agent_status_last_message_index` (`assigned_staff_id`,`status`,`last_message_at`),
  KEY `hcc_hospital_status_index` (`hospital_id`,`status`),
  KEY `hcc_requested_at_index` (`requested_at`),
  KEY `hcc_hospital_requested_at_index` (`hospital_id`,`requested_at`),
  KEY `hcc_agent_requested_at_index` (`assigned_staff_id`,`requested_at`),
  KEY `hcc_context_qna_fk` (`context_qna_id`),
  CONSTRAINT `hcc_assigned_staff_fk` FOREIGN KEY (`assigned_staff_id`) REFERENCES `account_staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hcc_context_qna_fk` FOREIGN KEY (`context_qna_id`) REFERENCES `hospital_chatbot_qnas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hcc_hospital_fk` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hcc_requester_fk` FOREIGN KEY (`requested_by_account_hospital_id`) REFERENCES `account_hospitals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 상담원 연결 세션';

CREATE TABLE `hospital_chatbot_consultation_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 메시지 ID',
  `consultation_id` bigint unsigned NOT NULL COMMENT '상담 ID',
  `sequence` bigint unsigned NOT NULL COMMENT '상담 내 단조 증가 순번',
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발신자 구분(HOSPITAL_USER, AGENT, SYSTEM)',
  `sender_id` bigint unsigned DEFAULT NULL COMMENT '발신자 계정 ID. SYSTEM은 비어 있다',
  `sender_name_snapshot` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '발신 당시 표시명',
  `client_message_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '클라이언트 중복 전송 방지 키',
  `message_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TEXT' COMMENT '메시지 종류(TEXT, SYSTEM_TEXT)',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '메시지 본문',
  `broadcast_pending` tinyint(1) NOT NULL DEFAULT '0' COMMENT '실시간 발행 대기 여부',
  `created_at` timestamp NOT NULL COMMENT '서버 저장 시각',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hccm_consultation_sequence_unique` (`consultation_id`,`sequence`),
  UNIQUE KEY `hccm_consultation_sender_client_unique` (`consultation_id`,`sender_type`,`sender_id`,`client_message_id`),
  KEY `hccm_consultation_created_at_index` (`consultation_id`,`created_at`),
  KEY `hccm_broadcast_pending_index` (`broadcast_pending`),
  CONSTRAINT `hccm_consultation_fk` FOREIGN KEY (`consultation_id`) REFERENCES `hospital_chatbot_consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 상담 메시지';

CREATE TABLE `hospital_chatbot_consultation_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '상담 상태 이력 ID',
  `consultation_id` bigint unsigned NOT NULL COMMENT '상담 ID',
  `from_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '이전 상태. 최초 생성은 비어 있다',
  `to_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '변경된 상태',
  `actor_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '수행 주체(HOSPITAL_USER, AGENT, SYSTEM)',
  `actor_id` bigint unsigned DEFAULT NULL COMMENT '수행자 계정 ID',
  `reason` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '변경 사유 코드',
  `occurred_at` timestamp NOT NULL COMMENT '발생 시각',
  `request_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '추적용 Request ID',
  PRIMARY KEY (`id`),
  KEY `hccsh_consultation_occurred_at_index` (`consultation_id`,`occurred_at`),
  CONSTRAINT `hccsh_consultation_fk` FOREIGN KEY (`consultation_id`) REFERENCES `hospital_chatbot_consultations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 상담 상태 전이 이력';

-- 이용 이벤트(열람·질문 클릭). 대시보드 집계용. 보관 180일.
CREATE TABLE `hospital_chatbot_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '이용 이벤트 ID',
  `event_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '이벤트 종류(WIDGET_OPENED, QNA_VIEWED)',
  `hospital_id` bigint unsigned NOT NULL COMMENT '세션의 병의원 ID. 본문 값이 아니라 인증 계정에서 정한다',
  `account_hospital_id` bigint unsigned DEFAULT NULL COMMENT '세션의 병의원 계정 ID. 감사용',
  `qna_id` bigint unsigned DEFAULT NULL COMMENT 'QNA_VIEWED일 때 본 Q&A ID. Q&A가 삭제돼도 유지한다',
  `source_page_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '이벤트 당시 호스트 화면 경로',
  `occurred_at` timestamp NOT NULL COMMENT '서버 수신 시각. 클라이언트 시계를 믿지 않는다',
  PRIMARY KEY (`id`),
  KEY `hce_occurred_idx` (`occurred_at`),
  KEY `hce_type_occurred_idx` (`event_type`,`occurred_at`),
  KEY `hce_qna_occurred_idx` (`qna_id`,`occurred_at`),
  KEY `hce_hospital_occurred_idx` (`hospital_id`,`occurred_at`),
  KEY `hce_account_fk` (`account_hospital_id`),
  CONSTRAINT `hce_account_fk` FOREIGN KEY (`account_hospital_id`) REFERENCES `account_hospitals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hce_hospital_fk` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='병의원 챗봇 이용 이벤트(열람·질문 클릭)';
