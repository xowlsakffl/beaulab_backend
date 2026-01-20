# AI Behavior Rules (Beaulab)

- 기존 구조( /admin, /admin/api, /api ) 경계를 절대 침범하지 않는다.
- /admin/* (Inertia 페이지)는 redirect + errors 흐름을 유지하고, JSON 에러를 강제하지 않는다.
- API(/api/*, /admin/api/*)는 ApiResponse 포맷을 반드시 사용한다.
- 불필요한 리팩터링, 폴더 구조 재편, 의존성 변경을 제안/수행하지 않는다.
- 확신이 없으면 코드를 생성하기 전에 먼저 관련 파일 위치를 확인하거나 질문한다.
