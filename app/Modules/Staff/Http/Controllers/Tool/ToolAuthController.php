<?php

namespace App\Modules\Staff\Http\Controllers\Tool;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class ToolAuthController
{
    public function showLoginForm(): View|RedirectResponse
    {
        $staff = Auth::guard('tool_staff')->user();

        if ($staff instanceof AccountStaff && Gate::forUser($staff)->allows('viewTool')) {
            return redirect()->route('tool.index');
        }

        return view('tools.login', [
            'dashboardUrl' => route('tool.index'),
            'loginUrl' => route('tool.login.submit'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'nickname' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'max:255'],
            ],
            [],
            [
                'nickname' => '아이디',
                'password' => '비밀번호',
            ],
        );

        $credentials = [
            'nickname' => trim((string) $validated['nickname']),
            'password' => (string) $validated['password'],
        ];

        if (! Auth::guard('tool_staff')->attempt($credentials)) {
            return back()
                ->withErrors(['nickname' => '아이디 또는 비밀번호가 일치하지 않습니다.'])
                ->onlyInput('nickname');
        }

        $request->session()->regenerate();

        $staff = Auth::guard('tool_staff')->user();

        if (! $staff instanceof AccountStaff || ! $staff->isActive()) {
            return $this->logoutWithError($request, '비활성화된 계정입니다.');
        }

        if (! Gate::forUser($staff)->allows('viewTool')) {
            return $this->logoutWithError($request, '내부도구 접근 권한이 없습니다.');
        }

        $staff->forceFill([
            'last_login_at' => now(),
        ])->save();

        Log::info('내부도구 로그인', [
            'staff_id' => $staff->id,
            'nickname' => $staff->nickname,
            'email' => $staff->email,
        ]);

        return redirect()->route('tool.index');
    }

    public function index(Request $request): View
    {
        $staff = $request->user('tool_staff');
        abort_unless($staff instanceof AccountStaff && Gate::forUser($staff)->allows('viewTool'), 403);

        return view('tools.index', [
            'staff' => $staff,
            'tools' => $this->tools(),
            'logoutUrl' => route('tool.logout'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $staff = Auth::guard('tool_staff')->user();

        Auth::guard('tool_staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($staff instanceof AccountStaff) {
            Log::info('내부도구 로그아웃', [
                'staff_id' => $staff->id,
                'nickname' => $staff->nickname,
                'email' => $staff->email,
            ]);
        }

        return redirect()->route('tool.login');
    }

    private function logoutWithError(Request $request, string $message): RedirectResponse
    {
        Auth::guard('tool_staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('tool.login')
            ->withErrors(['nickname' => $message])
            ->onlyInput('nickname');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tools(): array
    {
        return [
            [
                'name' => 'Horizon',
                'description' => '큐 워커 상태, 실패 작업, 처리량을 확인합니다.',
                'url' => $this->horizonUrl(),
                'enabled' => true,
                'badge' => 'Queue',
            ],
            [
                'name' => 'Telescope',
                'description' => '요청, 쿼리, 예외, 잡 실행 기록을 추적합니다.',
                'url' => $this->telescopeUrl(),
                'enabled' => (bool) config('telescope.enabled'),
                'badge' => 'Debug',
            ],
            [
                'name' => 'Swagger',
                'description' => 'API 명세와 테스트 콘솔을 엽니다.',
                'url' => $this->swaggerUrl(),
                'enabled' => $this->swaggerUrl() !== null,
                'badge' => 'API',
            ],
        ];
    }

    private function horizonUrl(): string
    {
        return url('/'.trim((string) config('horizon.path', 'horizon'), '/'));
    }

    private function telescopeUrl(): string
    {
        return url('/'.trim((string) config('telescope.path', 'telescope'), '/'));
    }

    private function swaggerUrl(): ?string
    {
        $configured = trim((string) env('INTERNAL_TOOL_SWAGGER_URL', ''));

        if ($configured === '') {
            return null;
        }

        if (str_starts_with($configured, 'http://') || str_starts_with($configured, 'https://')) {
            return $configured;
        }

        return url('/'.ltrim($configured, '/'));
    }
}
