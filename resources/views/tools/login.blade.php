<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>내부도구 로그인</title>
    <style>
        :root {
            --bg-start: #f5efe4;
            --bg-end: #dbe7f0;
            --panel: rgba(255, 252, 246, 0.92);
            --panel-strong: rgba(255, 255, 255, 0.72);
            --text: #172033;
            --muted: #5f6c7b;
            --line: rgba(23, 32, 51, 0.12);
            --accent: #0f766e;
            --accent-strong: #134e4a;
            --danger: #b91c1c;
            --shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Pretendard", "Noto Sans KR", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.22), transparent 28%),
                radial-gradient(circle at bottom right, rgba(180, 83, 9, 0.18), transparent 24%),
                linear-gradient(135deg, var(--bg-start) 0%, #ece4d7 42%, var(--bg-end) 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(100%, 1040px);
            display: grid;
            grid-template-columns: minmax(320px, 1.15fr) minmax(320px, 0.85fr);
            border: 1px solid var(--line);
            border-radius: 30px;
            overflow: hidden;
            background: var(--panel);
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
        }

        .hero {
            position: relative;
            padding: 52px;
            background:
                linear-gradient(180deg, rgba(15, 118, 110, 0.12), transparent 46%),
                linear-gradient(140deg, rgba(255, 255, 255, 0.66), rgba(255, 255, 255, 0.18));
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 40px;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto -60px -60px auto;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(15, 118, 110, 0.16), transparent 68%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent-strong);
        }

        .eyebrow::before {
            content: "";
            width: 34px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
        }

        h1 {
            margin: 0 0 16px;
            font-size: clamp(34px, 4vw, 52px);
            line-height: 1.04;
            letter-spacing: -0.05em;
        }

        .hero p {
            margin: 0;
            max-width: 34ch;
            font-size: 15px;
            line-height: 1.75;
            color: var(--muted);
        }

        .summary {
            display: grid;
            gap: 14px;
        }

        .summary-item {
            padding: 18px 20px;
            border: 1px solid rgba(15, 118, 110, 0.12);
            border-radius: 18px;
            background: var(--panel-strong);
        }

        .summary-item strong {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .summary-item span {
            font-size: 14px;
            line-height: 1.7;
            color: var(--muted);
            word-break: break-all;
        }

        .panel {
            padding: 52px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 28px;
            letter-spacing: -0.03em;
        }

        .panel-copy {
            margin: 0 0 28px;
            font-size: 14px;
            line-height: 1.75;
            color: var(--muted);
        }

        .error {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(185, 28, 28, 0.16);
            background: rgba(254, 242, 242, 0.92);
            color: var(--danger);
            font-size: 14px;
            line-height: 1.6;
        }

        form {
            display: grid;
            gap: 18px;
        }

        label {
            display: grid;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #344154;
        }

        input {
            width: 100%;
            height: 54px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.1);
            background: rgba(255, 255, 255, 0.86);
            padding: 0 16px;
            font-size: 15px;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        input:focus {
            border-color: rgba(15, 118, 110, 0.55);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
            transform: translateY(-1px);
        }

        button {
            height: 56px;
            border: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
        }

        .panel-note {
            margin-top: 18px;
            font-size: 13px;
            line-height: 1.7;
            color: var(--muted);
        }

        .panel-note a {
            color: var(--accent-strong);
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .hero,
            .panel {
                padding: 34px 24px;
            }
        }
    </style>
</head>
<body>
    @php($errorMessage = $errors->first('nickname') ?: $errors->first('password'))

    <main class="shell">
        <section class="hero">
            <div>
                <span class="eyebrow">Internal Tools</span>
                <h2>로그인 후 내부도구 허브로 이동합니다.</h2>
                <p>직원 계정으로 로그인하면 공용 대시보드에서 Horizon, Telescope, Swagger 같은 운영 도구를 같은 세션으로 선택해 열 수 있습니다.</p>
            </div>

            <div class="summary">
                <div class="summary-item">
                    <strong>로그인 후 이동</strong>
                    <span>{{ $dashboardUrl }}</span>
                </div>
                <div class="summary-item">
                    <strong>접근 보호</strong>
                    <span>통합 로그인, 허용 IP, 공용 권한 Gate를 함께 적용합니다.</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>내부도구 로그인</h2>
            <p class="panel-copy">한 번 로그인하면 내부도구 허브를 거쳐 필요한 운영 도구로 이동합니다. 권한이 없는 계정은 로그인 후에도 내부도구에 접근할 수 없습니다.</p>

            @if ($errorMessage !== '')
                <div class="error">{{ $errorMessage }}</div>
            @endif

            <form method="post" action="{{ $loginUrl }}">
                @csrf

                <label>
                    아이디
                    <input
                        type="text"
                        name="nickname"
                        value="{{ old('nickname') }}"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </label>

                <label>
                    비밀번호
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <button type="submit">로그인</button>
            </form>

            <p class="panel-note">
                로그인 성공 후 이동하는 내부도구 허브:
                <a href="{{ $dashboardUrl }}">{{ $dashboardUrl }}</a>
            </p>
        </section>
    </main>
</body>
</html>
