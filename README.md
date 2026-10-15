# 뷰랩 백엔드

병의원·회원·콘텐츠·상담·광고·포인트·직원 운영을 담당하는 Laravel API입니다. 서비스 공통 업무 기준서는 이 저장소에서 관리합니다.

- [시스템 기준서](docs/README.md), [시스템 개요](docs/overview.md), [기능 지도](docs/feature-map.md)
- [백엔드 구조](docs/technical/architecture.md), [API와 보안](docs/technical/api-security.md)
- [로컬 실행](docs/operations/local-development.md), [개발 데이터](docs/operations/development-data.md)
- [작업 규칙](docs/working-rules.md), [AGENTS](AGENTS.md)
- [프론트 저장소](https://github.com/beaulab/beaulab_new_frontend)

## 구성

PHP 8.3 이상, Laravel 12, MySQL 8, Redis를 사용합니다. 직원·병의원·일반회원·뷰티 API를 주체별로 구분하고 도메인별 Action·Query·Policy·Model에 업무를 배치합니다. 큐·스케줄러·실시간 서버는 HTTP 서버와 별도입니다.

의존성은 [composer.json](composer.json)과 잠금 파일, 스키마는 [마이그레이션](database/migrations), API 진입은 [routes/api.php](routes/api.php)가 기준입니다. 자동 API 문서는 내부 도구 접근 제한을 따릅니다.

## 실행 안전

기존 환경에서는 `composer setup`을 일반 실행 명령으로 쓰지 않습니다. 키 생성·마이그레이션이 포함됩니다. DB 변경·시드·큐·외부 발송·배포는 대상과 부작용을 확인한 뒤 실행합니다.

현재 구현과 미연결 기능·운영 미검증 사항은 기준서에 구분합니다. 문서 설명이 운영 배포 완료를 뜻하지 않습니다.
