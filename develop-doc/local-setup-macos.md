# macOS 로컬 API 환경 구축 기록

[설치·실행 절차](local-setup.md)는 Ubuntu 24.04 기준이며 macOS는 미검증으로 표시되어 있다. 이 문서는 Apple Silicon macOS(Darwin 24.4)에서 같은 절차를 수행한 기록이다. 기준일: 2026-09-23. Ubuntu 절차를 대체하지 않으며, 달라지는 부분과 확인한 범위만 남긴다.

## 1. 도구 준비

| 항목     | macOS에서 사용한 것                           | 주의                                                                         |
| -------- | --------------------------------------------- | ---------------------------------------------------------------------------- |
| PHP      | Homebrew `php` 8.5.10 (`/opt/homebrew/opt/php/bin`) | `php@8.2`가 기본 링크면 `php artisan`이 8.2로 잡혀 실패한다. PATH를 앞에 둔다 |
| Composer | Homebrew `composer` 2.10.3                    | apt 명령은 사용할 수 없다                                                    |
| MySQL 8  | Docker `mysql:8.4`                            | 호스트에 다른 MySQL·MariaDB가 있으면 포트·datadir이 충돌한다                 |
| Redis    | Docker `redis:7-alpine`                       | 확장 대신 Composer의 Predis를 쓰므로 PHP 확장 설치는 필요 없다               |

```bash
export PATH="/opt/homebrew/opt/php/bin:$PATH"
php -v
composer --version
```

PHP는 8.3 이상이어야 한다. Homebrew의 `mysql`·`mariadb` 수식은 datadir `/opt/homebrew/var/mysql`과 포트 3306을 공유하므로, 이미 다른 프로젝트의 DB가 그 자리에 있으면 네이티브 설치 대신 컨테이너를 사용한다.

## 2. MySQL·Redis 컨테이너

저장소 밖(예: 저장소 상위 디렉터리)에 compose 파일을 두고 실행한다. 비밀번호가 들어가므로 저장소에 커밋하지 않는다. 아래 값은 예시이며 `openssl rand -hex 16`으로 각자 생성한다.

```yaml
services:
  mysql:
    image: mysql:8.4
    container_name: beaulab-mysql
    restart: unless-stopped
    command:
      - --character-set-server=utf8mb4
      - --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: <생성한 root 비밀번호>
      MYSQL_DATABASE: beaulab_frontend_local
      MYSQL_USER: beaulab_frontend
      MYSQL_PASSWORD: <생성한 DB 비밀번호>
    ports:
      - "127.0.0.1:3307:3306"
    volumes:
      - beaulab-mysql-data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    container_name: beaulab-redis
    restart: unless-stopped
    ports:
      - "127.0.0.1:6379:6379"
    volumes:
      - beaulab-redis-data:/data

volumes:
  beaulab-mysql-data:
  beaulab-redis-data:
```

호스트 3306을 다른 DB가 점유하고 있어 MySQL을 **3307**로 매핑했다. 포트가 비어 있으면 3306을 그대로 써도 된다. 두 포트 모두 `127.0.0.1`에만 바인딩한다.

컨테이너가 `MYSQL_DATABASE`·`MYSQL_USER`를 만들므로 [설치·실행 절차](local-setup.md)의 `CREATE DATABASE`·`CREATE USER` SQL은 실행하지 않는다.

## 3. 환경 파일

`.env`가 없는 새 checkout에서만 복사한다.

```bash
cp --no-clobber .env.frontend.example .env
chmod 600 .env
```

예시 파일에서 바꾼 값은 다음과 같다.

| 설정                     | 값                                       |
| ------------------------ | ---------------------------------------- |
| `DB_PORT`                | `3307` (컨테이너 매핑 포트에 맞춘다)     |
| `DB_PASSWORD`            | compose의 `MYSQL_PASSWORD`와 같은 값     |
| `FRONTEND_SEED_PASSWORD` | 개발 로그인 계정용 별도 값, 최소 12자    |
| `FRONTEND_SEED_ENABLED`  | 최초 데이터 생성 때만 `true`             |

비밀번호는 `.env`에만 두고 저장소·문서에 남기지 않는다. 담당자 사이에서는 별도 경로로 전달한다.

## 4. 실행한 명령

```bash
composer install
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed --class=FrontendDevelopmentSeeder
php artisan storage:link
```

Seeder 실행 후 `FRONTEND_SEED_ENABLED=false`로 되돌리고 `php artisan config:clear`를 다시 실행한다.

## 5. 확인한 범위

- `composer install` 145개 패키지 설치, `package:discover` 정상.
- 마이그레이션 전체가 MySQL 8.4에서 통과했다.
- `FrontendDevelopmentSeeder`가 직원·사용자·병원·이벤트·채팅 기본 데이터를 생성했다. 목록은 [개발 데이터 사용 안내](development-data.md)와 같다.
- `php artisan serve` 기동 후 `GET /up`이 200을 반환했다.
- `Cache::put`/`Cache::get`으로 Redis 연결을 확인했다.

확인하지 않은 범위는 다음과 같다. 설치 완료 기준의 나머지 항목은 각자 환경에서 이어서 확인한다.

- 브라우저에서의 직원·사용자·병원 로그인과 화면 표시.
- 메일 전달. `MAIL_MAILER=array`이고 Mailpit을 설치하지 않았다.
- Reverb WebSocket 전달, Horizon 큐 처리, Scheduler.
- iOS 빌드와 실제 기기 연결.

## 6. 알려진 차이

- `config/filesystems.php`의 `rtrim(env('APP_URL'), '/')`는 `APP_URL`이 비면 PHP 8.5에서 deprecation을 출력한다. `.env`를 설정하면 나타나지 않는다.
- macOS에서는 `sudo service` 대신 Docker Desktop 또는 `brew services`로 의존 서비스를 관리한다.
