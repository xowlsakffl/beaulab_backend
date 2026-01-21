<laravel-boost-guidelines>
=== .ai/ai-behavior rules ===

# AI Behavior Rules (Beaulab)

- 기존 구조( /admin, /admin/api, /api ) 경계를 절대 침범하지 않는다.
- /admin/* (Inertia 페이지)는 redirect + errors 흐름을 유지하고, JSON 에러를 강제하지 않는다.
- API(/api/*, /admin/api/*)는 ApiResponse 포맷을 반드시 사용한다.
- 불필요한 리팩터링, 폴더 구조 재편, 의존성 변경을 제안/수행하지 않는다.
- 확신이 없으면 코드를 생성하기 전에 먼저 관련 파일 위치를 확인하거나 질문한다.

=== .ai/architecture rules ===

# Architecture (구조/흐름)

이 문서는 Beaulab 프로젝트의 **전체 구조와 요청 흐름**을 설명합니다.  
특히 “관리자 화면(Inertia) / 관리자 API / 앱 사용자 API”를 **명확히 분리**하는 것이 핵심입니다.

---

## 1. 큰 그림

본 프로젝트는 아래 3가지 영역으로 구성됩니다.

| 구분 | 목적 | URL Prefix | 응답 |
|---|---|---|---|
| 관리자 페이지 (Inertia) | 관리자 화면 렌더링/페이지 이동 | `/admin/*` | HTML + Inertia |
| 관리자 API | 관리자 화면의 동적 데이터 (테이블/필터/모달 CRUD) | `/admin/api/*` | JSON |
| 앱 사용자 API | 별도 앱(모바일/레거시/외부 클라이언트) 통신 | `/api/*` | JSON |

**핵심 원칙**
- 페이지 네비게이션/폼 흐름은 **Inertia 기본 흐름**을 따른다.
- “동적 UI(테이블/필터/모달)”는 **관리자 API(`/admin/api/*`)**로 처리한다.
- 앱 사용자 API는 **Sanctum 토큰 기반**으로 `/api/*`에 분리한다.

---

## 2. 폴더 구조(백엔드)

- `app/Modules/Admin/*`
    - 관리자 도메인 (세션 기반 인증, 관리자 전용 기능)
- `app/Modules/User/*`
    - 앱 사용자 도메인 (Sanctum 토큰 기반 API)
- `app/Shared/*`
    - 공통: `ApiResponse`, `ErrorCode`, `CustomException`, 공통 유틸/예외 등
- `routes/`
    - `web.php` : 관리자 Inertia 페이지 라우트(`/admin/*`)
    - `admin.php` : 관리자 API 라우트(`/admin/api/*`)
    - `api.php` : 앱 사용자 API 라우트(`/api/*`)

---

## 3. 프론트 구조(Inertia + React)

### 3.1 Inertia 페이지 경로 규칙
- Inertia는 `Inertia::render('{name}')`에서 전달된 name을 기준으로 아래 파일을 로드합니다.

- 페이지 파일 위치:
    - `resources/js/pages/**`

예)
- 서버: `Inertia::render('admin/dashboard')`
- 프론트: `resources/js/pages/admin/dashboard.tsx`

### 3.2 관리자 레이아웃 구성
- 관리자 앱 레이아웃: `resources/js/layouts/admin/app-layout.tsx`
- 관리자 인증 레이아웃: `resources/js/layouts/admin/auth-layout.tsx`
- 관리자 설정 레이아웃: `resources/js/layouts/admin/settings/layout.tsx`

---

## 4. 인증(Authentication)

### 4.1 관리자 (세션 기반)
- Guard: `admin` (session)
- 로그인: `/admin/login` (Fortify)
- 보호 페이지: `/admin/*` + `auth:admin`

### 4.2 앱 사용자 (토큰 기반)
- Guard: `sanctum` (token)
- 보호 API: `/api/*` + `auth:sanctum`
- 토큰 저장: `personal_access_tokens` 테이블

---

## 5. 라우팅/응답/예외 처리 기준

### 5.1 응답 타입 기준
- `/admin/*` : Inertia 응답(HTML/redirect/errors) 유지
- `/admin/api/*` : JSON (공통 포맷)
- `/api/*` : JSON (공통 포맷)

### 5.2 예외 처리의 기본 원칙
- API 구간(`/api/*`, `/admin/api/*`)은 `ApiResponse::error()`로 통일
- Inertia 페이지(`/admin/*`)는 기본 흐름(redirect + errors)을 깨지 않도록 JSON 에러를 강제하지 않음

자세한 내용은 [에러/예외 처리](./error-handling.md)를 참고합니다.

=== .ai/error-handling rules ===

# Error Handling

이 문서는 본 프로젝트의 **공통 예외 처리 구조와 규칙**을 설명합니다.  
신규 합류 개발자 또는 추후 유지보수 시, **왜 이런 구조를 선택했는지**를 빠르게 이해하는 것을 목표로 합니다.

---

## 1. 목적

- API 에러 응답 포맷을 **완전히 통일**
- 앱 사용자 API와 관리자 API를 **명확히 분리**
- Inertia 기반 관리자 페이지 흐름을 **깨지지 않게 보호**
- 운영 시 에러를 **traceId 기준으로 추적 가능**하게 함

---

## 2. 적용 범위

| 구분 | URL | 응답 형식 |
|----|----|----|
| 앱 사용자 API | `/api/*` | JSON |
| 관리자 API | `/admin/api/*` | JSON |
| 관리자 페이지 (Inertia) | `/admin/*` | HTML / Inertia 응답 |

> `/admin/*` 페이지 라우트에는 JSON 에러를 강제하지 않음  
> (Inertia 페이지 흐름 보호 목적)

---

## 3. 기본 에러 응답 포맷

모든 API 에러는 아래 형식을 따릅니다.

> json { "success": false, "error": { "code": "USER_NOT_FOUND", "message": "사용자를 찾을 수 없습니다." }, "traceId": "c2f1a3b4-..." }

---

## 4. JSON 응답을 반환하는 기준

본 프로젝트는 “요청이 API인지”를 아래 기준으로 판단하여 JSON 에러 응답을 반환합니다.

### 4.1 경로 기반 (강제 JSON)
- 앱 사용자 API: `/api/*`
- 관리자 API: `/admin/api/*`

위 경로는 **항상 JSON**으로 렌더링합니다.

### 4.2 헤더 기반 (선택 JSON)
- 위 경로가 아니더라도, 클라이언트가 `Accept: application/json`을 보내면 JSON으로 응답합니다.

> React에서 `fetch/axios` 호출 시 `Accept: application/json`을 명시하면  
> `/admin/*` 페이지 내부에서도 “데이터 호출”에 한해 JSON 에러 포맷을 사용할 수 있습니다.  
> 단, **페이지 네비게이션 자체(`/admin/*`)는 Inertia 기본 흐름을 유지**합니다.

---

## 5. Inertia 페이지를 보호하는 이유

`/admin/*`는 Inertia 기반 페이지 라우트이며, Laravel의 기본 동작(redirect + errors)이 UX에 최적화되어 있습니다.

- Validation 실패: redirect back + errors 공유
- 인증 실패: 로그인 페이지로 redirect
- CSRF(419) 등 웹 흐름: 기본 핸들링 유지

따라서 `/admin/*`에 대해 JSON 에러를 무조건 강제하면 아래 문제가 발생할 수 있습니다.

- 화면 전환이 깨지거나(JSON만 찍힘), 폼 오류 표시 흐름이 꼬임
- 로그인/권한 오류가 redirect가 아니라 JSON으로 반환되어 UX가 어색해짐

결론: **API는 JSON 통일 / 페이지는 Inertia 기본 흐름 유지**가 안정적입니다.

---

## 6. traceId 정책 (요청 추적)

### 6.1 RequestId 미들웨어
- 요청 헤더 `X-Request-Id`가 있으면 그 값을 사용
- 없으면 서버에서 UUID를 생성
- 생성/확정된 traceId는:
  - request attribute로 저장되어 어디서든 접근 가능
  - response 헤더 `X-Request-Id`로 내려줌
  - JSON 응답에는 `traceId` 필드를 포함

### 6.2 로그 컨텍스트
예외 발생 시 로그에는 최소한 아래 컨텍스트가 포함됩니다.

- traceId
- path
- method

운영에서 “한 번의 요청”을 traceId 기준으로 빠르게 추적할 수 있도록 합니다.

---

## 7. 예외 → ErrorCode 매핑 규칙

본 프로젝트는 예외를 아래 규칙으로 `ErrorCode`와 HTTP status에 매핑합니다.

| 예외 | HTTP | ErrorCode | 비고 |
|---|---:|---|---|
| `ValidationException` | 422 | `INVALID_REQUEST` | `details`에 validation errors 포함 |
| `AuthenticationException` | 401 | `UNAUTHORIZED` | 로그인 필요 |
| `AuthorizationException` | 403 | `FORBIDDEN` | 권한 없음 |
| `ModelNotFoundException` | 404 | `NOT_FOUND` | 리소스 없음 |
| `CustomException` | ErrorCode에 따름 | ErrorCode에 따름 | 도메인/비즈니스 에러 |
| `QueryException` | 500 | `DB_ERROR` | 운영에서는 상세 노출 금지 |
| 기타 `Throwable` | 500 | `INTERNAL_ERROR` | 알 수 없는 서버 오류 |

---

## 8. 관리자 vs 앱 메시지 정책

같은 ErrorCode라도, 사용자에게 노출되는 메시지는 대상에 따라 다릅니다.

- 앱 사용자(`/api/*`): 짧고 안전한 메시지 (민감정보 노출 최소화)
- 관리자(`/admin/api/*`): 운영에 도움이 되는 힌트를 조금 더 허용 (그래도 민감정보는 금지)

예)
- 앱: “서버 오류가 발생했습니다.”
- 관리자: “서버 오류(관리자) - 로그를 확인하세요.”

---

## 9. 디버그 정보 노출 정책

원칙: **운영환경에서는 상세 에러(쿼리/바인딩/스택)를 API 응답으로 내리지 않습니다.**

예외적으로, 관리자 API이고 `APP_DEBUG=true`인 경우에만 제한적으로 details를 포함할 수 있습니다.
- QueryException: sql, bindings 등
- Throwable: exception class, message 등

---

## 10. 샘플 응답

### 10.1 Validation (422)

>json { "success": false, "error": { "code": "INVALID_REQUEST", "message": "요청 값이 올바르지 않습니다.", "details": { "email": ["이메일 형식이 올바르지 않습니다."] } }, "traceId": "..." }

### 10.2 Unauthorized (401)
>json { "success": false, "error": { "code": "UNAUTHORIZED", "message": "인증이 필요합니다." }, "traceId": "..." }

### 10.3 Internal Error (500)
>json { "success": false, "error": { "code": "INTERNAL_ERROR", "message": "서버 오류가 발생했습니다." }, "traceId": "..." }

---

## 11. 운영 팁

- 클라이언트(앱/관리자 프론트)는 에러 발생 시 `traceId`를 함께 로그/리포트에 남길 것
- 서버 로그는 `traceId`로 검색 가능한 형태를 유지할 것
- API 응답에는 민감정보(토큰/SQL 전체/개인정보)가 포함되지 않도록 주의할 것

=== .ai/README rules ===

# Beaulab 개발 문서

이 폴더는 Beaulab 프로젝트의 **구조/규칙/운영 기준**을 문서로 정리해두는 공간입니다.  
신규 합류 시 “왜 이렇게 했는지”를 빠르게 이해하고, 팀 내 구현 기준을 통일하는 것을 목표로 합니다.

(개발자 안민성)
## 문서 목록

- [아키텍처 & 흐름](./architecture.md)
- [에러/예외 처리](./error-handling.md)

## 빠른 요약 (핵심 규칙)

- 관리자 화면은 **Inertia(React) 페이지 렌더링**으로 구성한다. (`/admin/*`)
- 관리자 화면에서 **테이블/필터/모달 CRUD는 관리자 API(`/admin/api/*`)를 호출**한다.
- 앱 사용자 API는 `/api/*`로 분리한다.
- API(`/api/*`, `/admin/api/*`)는 공통 `ApiResponse` 포맷으로 에러를 통일한다.
- Inertia 페이지(`/admin/*`)는 redirect + errors 등 기본 흐름을 깨지 않도록 JSON 에러를 강제하지 않는다.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.28
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- laravel/wayfinder (WAYFINDER) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== inertia-laravel/core rules ===

## Inertia

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use the `search-docs` tool for accurate guidance on all things Inertia.

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Deferred props.
- Infinite scrolling using merging props and `WhenVisible`.
- Lazy loading data on scroll.
- Polling.
- Prefetching.

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use the `search-docs` tool with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use the `search-docs` tool with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use the `search-docs` tool with a query of `form component resetting` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client-side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use the `search-docs` tool to check Wayfinder correct usage before implementing any features.
- Always prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`).
- Avoid default controller imports (prevents tree-shaking).
- Run `php artisan wayfinder:generate` after route changes if Vite plugin isn't installed.

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`.
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`.
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`.
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`.
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`.
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`.
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`.
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`.

### Example Usage

<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)...
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>


### Wayfinder + Inertia
If your application uses the `<Form>` component from Inertia, you can use Wayfinder to generate form action and method automatically.
<code-snippet name="Wayfinder Form Component (React)" lang="typescript">

<Form {...store.form()}><input name="title" /></Form>

</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>

=== inertia-react/v2/forms rules ===

## Inertia v2 + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
