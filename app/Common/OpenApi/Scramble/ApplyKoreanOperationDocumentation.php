<?php

namespace App\Common\OpenApi\Scramble;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter as OpenApiParameter;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionNamedType;

final class ApplyKoreanOperationDocumentation extends OperationExtension
{
    /**
     * @var array<string, string>
     */
    private const ACTOR_LABELS = [
        'staff' => '관리자',
        'user' => '앱 사용자',
        'hospital' => '병의원 계정',
        'beauty' => '뷰티 계정',
    ];

    /**
     * @var array<string, string>
     */
    private const RESOURCE_LABELS = [
        'auth' => '인증',
        'profile' => '프로필',
        'password' => '비밀번호',
        'notes' => '관리자 메모',
        'dashboard' => '대시보드',
        'hospital-features' => '병의원 특징',
        'hospitals' => '병의원',
        'categories' => '카테고리',
        'hashtags' => '해시태그',
        'beauties' => '뷰티업체',
        'users' => '일반회원',
        'doctors' => '의사',
        'experts' => '뷰티전문가',
        'videos' => '동영상',
        'talks' => '토크',
        'talk-comments' => '토크 댓글',
        'notices' => '공지사항',
        'faqs' => 'FAQ',
        'chats' => '채팅',
        'blocks' => '차단 사용자',
        'notifications' => '알림',
    ];

    /**
     * @var array<string, string>
     */
    private const PATH_SUMMARIES = [
        'post staff/auth/login' => '관리자 로그인',
        'post staff/auth/logout' => '관리자 로그아웃',
        'get staff/profile' => '관리자 프로필 조회',
        'put staff/profile' => '관리자 프로필 수정',
        'patch staff/profile' => '관리자 프로필 수정',
        'put staff/password' => '관리자 비밀번호 변경',
        'patch staff/password' => '관리자 비밀번호 변경',
        'get staff/dashboard' => '관리자 대시보드 조회',
        'post staff/hospitals/check-name' => '병의원명 중복 확인',
        'post staff/hospitals/check-business-number' => '사업자등록번호 중복 확인',
        'get staff/categories/selector' => '카테고리 선택 목록 조회',
        'get staff/doctors/hospital-options' => '의사 등록용 병의원 옵션 조회',
        'get staff/videos/hospital-options' => '동영상 등록용 병의원 옵션 조회',
        'get staff/videos/doctor-options' => '동영상 등록용 의사 옵션 조회',
        'get staff/videos/{video}/download-video-file' => '동영상 파일 다운로드',
        'patch staff/talks/status' => '토크 상태 일괄 수정',
        'patch staff/talk-comments/status' => '토크 댓글 상태 일괄 수정',
        'post staff/notices/editor-images' => '공지사항 에디터 이미지 업로드',
        'delete staff/notices/editor-images' => '공지사항 에디터 임시 이미지 정리',
        'post staff/faqs/editor-images' => 'FAQ 에디터 이미지 업로드',
        'delete staff/faqs/editor-images' => 'FAQ 에디터 임시 이미지 정리',

        'post user/auth/login' => '앱 사용자 로그인',
        'post user/auth/logout' => '앱 사용자 로그아웃',
        'get user/profile' => '앱 사용자 프로필 조회',
        'put user/profile' => '앱 사용자 프로필 수정',
        'patch user/profile' => '앱 사용자 프로필 수정',
        'put user/password' => '앱 사용자 비밀번호 변경',
        'patch user/password' => '앱 사용자 비밀번호 변경',
        'post user/chats/messages' => '새 채팅 첫 메시지 전송',
        'get user/chats/{chat}/messages' => '채팅 메시지 목록 조회',
        'post user/chats/{chat}/messages' => '채팅 메시지 전송',
        'post user/chats/{chat}/read' => '채팅 읽음 처리',
        'put user/chats/{chat}/notifications' => '채팅 알림 설정 수정',
        'patch user/chats/{chat}/notifications' => '채팅 알림 설정 수정',
        'post user/notifications/devices' => '알림 기기 등록',
        'post user/notifications/devices/revoke' => '알림 기기 해제',
        'get user/notifications/preferences' => '알림 수신 설정 조회',
        'put user/notifications/preferences' => '알림 수신 설정 수정',
        'patch user/notifications/preferences' => '알림 수신 설정 수정',
        'post user/notifications/read-all' => '알림 전체 읽음 처리',
        'get user/notifications/unread-count' => '읽지 않은 알림 수 조회',
        'post user/notifications/{notificationInbox}/read' => '알림 읽음 처리',
        'post user/talks' => '토크 생성',
        'delete user/talks/{talk}' => '토크 삭제',
        'post user/talks/{talk}/comments' => '토크 댓글 생성',
        'delete user/talks/{talk}/comments/{comment}' => '토크 댓글 삭제',
        'post user/talks/{talk}/poll-votes' => '토크 투표',

        'post hospital/auth/login' => '병의원 계정 로그인',
        'post hospital/auth/logout' => '병의원 계정 로그아웃',
        'get hospital/profile' => '병의원 계정 프로필 조회',
        'put hospital/profile' => '병의원 계정 프로필 수정',
        'patch hospital/profile' => '병의원 계정 프로필 수정',
        'put hospital/password' => '병의원 계정 비밀번호 변경',
        'patch hospital/password' => '병의원 계정 비밀번호 변경',
        'post hospital/videos' => '병의원 동영상 게시 요청',
        'post hospital/videos/{video}/cancel' => '병의원 동영상 게시 요청 취소',

        'post beauty/auth/login' => '뷰티 계정 로그인',
        'post beauty/auth/logout' => '뷰티 계정 로그아웃',
        'get beauty/profile' => '뷰티 계정 프로필 조회',
        'put beauty/profile' => '뷰티 계정 프로필 수정',
        'patch beauty/profile' => '뷰티 계정 프로필 수정',
        'put beauty/password' => '뷰티 계정 비밀번호 변경',
        'patch beauty/password' => '뷰티 계정 비밀번호 변경',
    ];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $path = $this->documentationPath($operation->path);
        $summary = $this->summary($operation, $path);

        $operation
            ->summary($summary)
            ->description($this->description($operation, $path, $summary))
            ->setTags([$this->tag($path)]);

        $requestAttributes = $this->requestAttributes($routeInfo);

        foreach ($operation->parameters as $parameter) {
            if (! $parameter instanceof OpenApiParameter) {
                continue;
            }

            $fieldDescription = KoreanOpenApiDescriptions::field($parameter->name, $requestAttributes)
                ?? $this->routeParameterDescription($parameter->name);

            if ($fieldDescription === null) {
                continue;
            }

            $parameter->description(trim(implode("\n", array_filter([
                $fieldDescription,
                $parameter->description,
            ]))));
        }
    }

    private function documentationPath(string $path): string
    {
        $path = trim($path, '/');

        if (str_starts_with($path, 'api/v1/')) {
            return substr($path, strlen('api/v1/'));
        }

        if (str_starts_with($path, 'v1/')) {
            return substr($path, strlen('v1/'));
        }

        return $path;
    }

    private function summary(Operation $operation, string $path): string
    {
        $key = strtolower($operation->method).' '.$path;
        if (isset(self::PATH_SUMMARIES[$key])) {
            return self::PATH_SUMMARIES[$key];
        }

        $segments = $this->segments($path);
        $resource = $this->resourceLabel($segments);
        $method = strtolower($operation->method);

        if ($method === 'get') {
            return $this->hasRouteParameter($segments)
                ? "{$resource} 상세 조회"
                : "{$resource} 목록 조회";
        }

        if ($method === 'delete') {
            return "{$resource} 삭제";
        }

        if (in_array($method, ['put', 'patch'], true)) {
            return "{$resource} 수정";
        }

        if ($method === 'post' && $this->hasRouteParameter($segments)) {
            return "{$resource} 수정";
        }

        return "{$resource} 생성";
    }

    private function description(Operation $operation, string $path, string $summary): string
    {
        $segments = $this->segments($path);
        $actor = self::ACTOR_LABELS[$segments[0] ?? ''] ?? 'API 사용자';
        $method = strtoupper($operation->method);

        return "{$summary} API입니다.\n\n- 대상: {$actor}\n- 경로: {$method} /api/v1/{$path}";
    }

    private function tag(string $path): string
    {
        $segments = $this->segments($path);
        $actor = self::ACTOR_LABELS[$segments[0] ?? ''] ?? '공통';
        $resource = $this->resourceLabel($segments);

        return "{$actor} / {$resource}";
    }

    /**
     * @return array<int, string>
     */
    private function segments(string $path): array
    {
        return array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
    }

    /**
     * @param array<int, string> $segments
     */
    private function resourceLabel(array $segments): string
    {
        $resource = $segments[1] ?? $segments[0] ?? 'API';

        if ($resource === 'notifications' && isset($segments[2]) && $segments[2] === 'preferences') {
            return '알림 수신 설정';
        }

        if ($resource === 'notifications' && isset($segments[2]) && $segments[2] === 'devices') {
            return '알림 기기';
        }

        return self::RESOURCE_LABELS[$resource] ?? str_replace('-', ' ', $resource);
    }

    /**
     * @param array<int, string> $segments
     */
    private function hasRouteParameter(array $segments): bool
    {
        foreach ($segments as $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function requestAttributes(RouteInfo $routeInfo): array
    {
        $reflection = $routeInfo->reflectionMethod();
        if ($reflection === null) {
            return [];
        }

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $className = $type->getName();
            if (! is_subclass_of($className, FormRequest::class)) {
                continue;
            }

            return KoreanOpenApiDescriptions::requestAttributesForClass($className);
        }

        return [];
    }

    private function routeParameterDescription(string $name): ?string
    {
        $name = trim($name, '{}');

        return [
            'hospital' => '병의원 ID',
            'beauty' => '뷰티업체 ID',
            'doctor' => '의사 ID',
            'expert' => '뷰티전문가 ID',
            'video' => '동영상 ID',
            'talk' => '토크 ID',
            'faq' => 'FAQ ID',
            'notice' => '공지사항 ID',
            'category' => '카테고리 ID',
            'hashtag' => '해시태그 ID',
            'user' => '일반회원 ID',
            'chat' => '채팅방 ID',
            'note' => '관리자 메모 ID',
            'notificationInbox' => '알림함 ID',
            'blockedUserId' => '차단 대상 사용자 ID',
        ][$name] ?? null;
    }
}
