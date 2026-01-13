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

```json
{
  "success": false,
  "error": {
    "code": "USER_NOT_FOUND",
    "message": "사용자를 찾을 수 없습니다."
  },
  "traceId": "c2f1a3b4-..."
}
