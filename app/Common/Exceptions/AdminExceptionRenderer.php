<?php

namespace App\Common\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class AdminExceptionRenderer
{
    public function handles(Request $request): bool
    {
        return $request->is('admin/*');
    }

    public function render(\Throwable $e, Request $request): Response|RedirectResponse|null
    {
        if (! $this->handles($request)) {
            return null;
        }

        // Inertia validation 기본 동작 유지(redirect back + errors props)
        if ($e instanceof ValidationException) {
            return null;
        }

        // 인증: 관리자 로그인으로
        if ($e instanceof AuthenticationException) {
            return redirect()->guest(route('admin.login'))
                ->with('error', AdminErrorCode::UNAUTHORIZED->message());
        }

        // 인가(권한) 403
        if ($e instanceof AuthorizationException) {
            return $this->renderAdminError($request, AdminErrorCode::FORBIDDEN);
        }

        // 모델 없음 404
        if ($e instanceof ModelNotFoundException) {
            return $this->renderAdminError($request, AdminErrorCode::NOT_FOUND);
        }

        // HTTP 예외들(404/419/405 등)
        if ($e instanceof HttpExceptionInterface) {
            return $this->renderHttpException($e, $request);
        }

        // 500: 운영환경에서만 Inertia 500 페이지로 통일
        if (! config('app.debug')) {
            return $this->renderAdminError($request, AdminErrorCode::INTERNAL_ERROR);
        }

        // 디버그 환경에서는 기본 예외 페이지(스택트레이스) 유지
        return null;
    }

    private function renderHttpException(HttpExceptionInterface $e, Request $request): Response|RedirectResponse
    {
        $status = $e->getStatusCode();

        // 419: 세션/CSRF 만료 → 관리자 로그아웃 + 재로그인 유도
        if ($status === 419) {
            try { auth('admin')->logout(); } catch (\Throwable) {}

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->guest(route('admin.login'))
                ->with('error', AdminErrorCode::SESSION_EXPIRED->message());
        }

        $error = match ($status) {
            403 => AdminErrorCode::FORBIDDEN,
            404 => AdminErrorCode::NOT_FOUND,
            405 => AdminErrorCode::METHOD_NOT_ALLOWED,
            default => AdminErrorCode::INTERNAL_ERROR,
        };

        return $this->renderAdminError($request, $error);
    }

    private function renderAdminError(Request $request, AdminErrorCode $error, ?string $overrideMessage = null): Response
    {
        return Inertia::render($error->page(), [
            'status'  => $error->status(),
            'code'    => $error->value, // UX 힌트용
            'message' => $overrideMessage ?? $error->message(),
            'traceId' => $request->attributes->get('traceId'),
        ])->toResponse($request)->setStatusCode($error->status());
    }
}
