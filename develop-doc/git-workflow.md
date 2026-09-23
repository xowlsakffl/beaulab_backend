# Git 협업 규칙

백엔드 개발자와 AI 작업 도구에 동일하게 적용한다. 기준일: 2026-09-22.

## 한눈에 보는 규칙

| 항목        | 기준                                                        |
| ----------- | ----------------------------------------------------------- |
| 업무 저장소 | `https://github.com/beaulab/beaulab_new_dev.git` (`origin`) |
| 기준 브랜치 | `master`                                                    |
| 작업 브랜치 | `feat/작업명`, `fix/작업명`, `docs/작업명`                  |
| 커밋        | `type(scope): 변경 내용` — scope는 생략 가능                |
| 변경 단위   | 한 가지 목적. 기능 수정에 무관한 포맷·리팩터링을 섞지 않음  |
| 병합        | 작업 브랜치 → 검증 → PR → `master`                          |
| 되돌리기    | 공유된 커밋은 원칙적으로 `git revert`                       |

현재 인원이 적으므로 담당자가 PR을 직접 검토·병합할 수 있다. 별도 승인자가 반드시 있어야 작업할 수 있다는 의미는 아니다. 협업자가 늘어나면 공통 코드·API 계약 변경은 상대 담당자가 리뷰한다. 사용자가 명시적으로 지시한 브랜치·커밋·푸시 범위가 있으면 그 범위 안에서 수행한다.

## 1. 작업 시작

```bash
git status --short --branch
git remote -v
git fetch origin
git switch master
git pull --ff-only origin master
git switch -c fix/chat-retry
```

위 예시는 작업 트리가 깨끗한 새 작업 기준이다. 진행 중인 변경이 있으면 소유자와 작업 목적을 확인하고 보존한다. 임의로 reset·stash·삭제하여 작업 트리를 비우지 않는다. 기존 작업을 계속할 때는 해당 브랜치를 유지한다.

## 2. 커밋 작성

| type       | 사용할 때                 | 예시                               |
| ---------- | ------------------------- | ---------------------------------- |
| `feat`     | 기능 추가                 | `feat(auth): 로그인 기능 추가`     |
| `fix`      | 결함 수정                 | `fix(chat): 재전송 중복 처리 수정` |
| `docs`     | 문서 변경                 | `docs: 개발 환경 안내 수정`        |
| `refactor` | 동작을 유지하는 코드 개선 | `refactor(auth): 인증 처리 정리`   |
| `test`     | 테스트 변경               | `test(auth): 만료 처리 검증 추가`  |
| `infra`    | CI·빌드·배포 설정         | `infra: 검증 워크플로 추가`        |
| `chore`    | 의존성 등 유지 관리       | `chore: 개발 의존성 갱신`          |
| `style`    | 동작 변화 없는 포맷       | `style: 변경 파일 포맷 정리`       |

scope 예: `auth, hospital, wallet, chat, notification`. 화면 기능·디자인 동작 변경을 단순 포맷인 `style`로 분류하지 않는다.

```bash
git diff --check
git diff
git add <이번 작업 파일>
git diff --cached
git commit -m "docs: 개발 환경 안내 수정"
git push -u origin <작업 브랜치>
```

`<...>`는 실제 경로·브랜치로 바꾼다. `git add .`로 다른 작업을 무심코 포함하지 않는다. 환경 파일, 비밀번호, 토큰, 서명키, 실제 고객 데이터는 커밋하지 않는다.

## 3. 검증과 PR

코드 변경은 영향 범위에 맞게 검사한다. 아래 명령은 전체 확인이 필요한 경우의 기준이며 문서만 변경했으면 링크·포맷·diff 확인으로 충분하다.

```bash
composer validate --strict
composer audit --locked --no-interaction
vendor/bin/phpunit --do-not-cache-result
```

테스트는 전용 MySQL·Redis와 안전한 발송 설정을 확인한 격리 환경에서 실행한다. 운영·공유 개발 DB에서 테스트, 전체 Seeder, migrate:fresh를 실행하지 않는다. 의존성 변경은 `composer.json`과 `composer.lock`을 함께 반영한다.

PR은 아래 네 가지면 충분하다.

- **변경:** 어떤 문제를 해결했고 동작이 어떻게 바뀌는가
- **영향:** 변경 앱·도메인, API·환경변수·데이터 변경 여부
- **검증:** 실행한 검사와 결과, 확인하지 못한 항목
- **배포:** 선행 배포·호환성·되돌리기 시 주의점

스키마 변경은 [운영 절차](operations.md#개발-단계의-마이그레이션-기준)를 따른다. 이미 생성 마이그레이션이 적용된 공유 DB에는 파일 수정만으로 변경이 반영되지 않는다. 실제 스키마 대조와 데이터 보존 방안을 PR에 남긴다. 코드 revert가 이미 바뀐 DB·발송 결과까지 되돌리지는 않는다.

## 4. 충돌·오류 처리

| 상황                  | 처리                                                            |
| --------------------- | --------------------------------------------------------------- |
| 기준 브랜치 변경 반영 | 작업 브랜치에서 `git fetch origin` 후 `git merge origin/master` |
| 충돌 발생             | 양쪽 변경 목적을 확인하고 해결한 뒤 관련 검사 재실행            |
| 병합을 중단할 때      | 진행 중인 merge에 한해 `git merge --abort`                      |
| 공유된 잘못된 커밋    | `git revert <커밋>` 후 검증·PR. merge commit은 부모 선택 확인   |
| 공유 브랜치 이력 변경 | 강제 푸시·reset으로 임의 재작성하지 않음                        |

충돌 파일을 통째로 ours/theirs로 덮지 않는다. 잠금 파일 충돌은 의존성 변경을 먼저 정리하고 해당 패키지 관리자로 다시 생성·검증한다.

## 5. 자동화 상태

`.github/workflows/tests.yml`은 master/main/develop 대상 의존성 검증·취약점 점검·PHP 구문 검사·MySQL/Redis 테스트를 정의한다. 실제 실행 성공과 필수 검사 설정은 PR에서 확인한다. 레거시 linter 워크플로는 대상 브랜치와 명령이 달라 동일한 보장으로 취급하지 않는다.
원격 브랜치 보호와 필수 승인 설정은 별도 확인 대상이다. 문서 작성만으로 자동 적용되지는 않는다.
