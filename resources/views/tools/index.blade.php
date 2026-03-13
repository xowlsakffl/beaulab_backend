<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>내부도구 허브</title>
    <style>
        :root {
            --bg-top: #f4ecdf;
            --bg-bottom: #dee9f3;
            --panel: rgba(255, 251, 244, 0.84);
            --panel-strong: rgba(255, 255, 255, 0.76);
            --text: #162033;
            --muted: #5c6b7c;
            --line: rgba(22, 32, 51, 0.1);
            --accent: #0f766e;
            --accent-strong: #115e59;
            --accent-soft: rgba(15, 118, 110, 0.1);
            --disabled: #94a3b8;
            --shadow: 0 26px 72px rgba(15, 23, 42, 0.16);
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
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.18), transparent 28%),
                radial-gradient(circle at right bottom, rgba(191, 219, 254, 0.26), transparent 34%),
                linear-gradient(180deg, var(--bg-top) 0%, var(--bg-bottom) 100%);
        }

        .page {
            width: min(100%, 1180px);
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 34px;
            border-radius: 30px;
            border: 1px solid var(--line);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0.54)),
                linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(15, 23, 42, 0.02));
            box-shadow: var(--shadow);
        }

        .hero::after {
            content: "";
            position: absolute;
            top: -90px;
            right: -60px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(15, 118, 110, 0.16), transparent 70%);
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent-strong);
        }

        .eyebrow::before {
            content: "";
            width: 36px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(34px, 4vw, 52px);
            line-height: 1.02;
            letter-spacing: -0.05em;
        }

        .hero-copy {
            margin: 0;
            max-width: 46ch;
            font-size: 15px;
            line-height: 1.8;
            color: var(--muted);
        }

        .account-box {
            min-width: 260px;
            padding: 18px 18px 16px;
            border-radius: 22px;
            border: 1px solid rgba(15, 118, 110, 0.12);
            background: var(--panel-strong);
            backdrop-filter: blur(12px);
        }

        .account-label {
            display: block;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .account-name {
            margin: 0 0 6px;
            font-size: 22px;
            letter-spacing: -0.03em;
        }

        .account-meta {
            margin: 0;
            font-size: 13px;
            line-height: 1.7;
            color: var(--muted);
            word-break: break-word;
        }

        .logout-form {
            margin-top: 16px;
        }

        .logout-button {
            width: 100%;
            height: 44px;
            border: 0;
            border-radius: 14px;
            background: #162033;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .hero-footer {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .hero-stat {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(22, 32, 51, 0.08);
            background: rgba(255, 255, 255, 0.58);
        }

        .hero-stat strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .hero-stat span {
            display: block;
            font-size: 13px;
            line-height: 1.7;
            color: var(--muted);
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 34px 0 18px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 24px;
            letter-spacing: -0.03em;
        }

        .section-head p {
            margin: 0;
            font-size: 14px;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 24px;
            border-radius: 24px;
            border: 1px solid var(--line);
            background: var(--panel);
            box-shadow: 0 16px 42px rgba(15, 23, 42, 0.08);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }

        .status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #16a34a;
        }

        .status.disabled::before {
            background: var(--disabled);
        }

        .card h3 {
            margin: 0 0 8px;
            font-size: 24px;
            letter-spacing: -0.03em;
        }

        .card p {
            margin: 0;
            font-size: 14px;
            line-height: 1.75;
            color: var(--muted);
        }

        .card-footer {
            margin-top: auto;
        }

        .tool-link,
        .tool-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.01em;
            text-decoration: none;
        }

        .tool-link {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
        }

        .tool-disabled {
            background: rgba(148, 163, 184, 0.18);
            color: #667085;
        }

        .note {
            margin-top: 20px;
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px solid rgba(22, 32, 51, 0.08);
            background: rgba(255, 255, 255, 0.55);
            font-size: 13px;
            line-height: 1.8;
            color: var(--muted);
        }

        code {
            padding: 2px 6px;
            border-radius: 8px;
            background: rgba(15, 118, 110, 0.08);
            color: var(--accent-strong);
            font-family: "JetBrains Mono", "Consolas", monospace;
            font-size: 12px;
        }

        @media (max-width: 980px) {
            .hero-top,
            .section-head {
                flex-direction: column;
            }

            .hero-footer,
            .grid {
                grid-template-columns: 1fr;
            }

            .account-box {
                min-width: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <section class="hero">
            <div class="hero-top">
                <div>
                    <span class="eyebrow">Internal Tools</span>
                    <h1>운영 도구 허브</h1>
                    <p class="hero-copy">내부 운영 도구는 같은 세션과 같은 권한 기준으로 묶여 있습니다. 필요한 도구를 선택해서 바로 이동하고, 접근 정책은 공용 Gate와 허용 IP로 통일합니다.</p>
                </div>

                <aside class="account-box">
                    <span class="account-label">현재 로그인</span>
                    <p class="account-name">{{ $staff->name ?: $staff->nickname }}</p>
                    <p class="account-meta">
                        아이디: {{ $staff->nickname }}<br>
                        이메일: {{ $staff->email ?: '-' }}
                    </p>

                    <form class="logout-form" method="post" action="{{ $logoutUrl }}">
                        @csrf
                        <button class="logout-button" type="submit">로그아웃</button>
                    </form>
                </aside>
            </div>

            <div class="hero-footer">
                <div class="hero-stat">
                    <strong>통합 인증</strong>
                    <span>한 번 로그인하면 내부도구 공용 세션으로 Horizon, Telescope, Swagger를 공통 진입합니다.</span>
                </div>
                <div class="hero-stat">
                    <strong>권한 기준</strong>
                    <span><code>viewTool</code> Gate를 통과한 staff만 내부도구를 볼 수 있습니다.</span>
                </div>
                <div class="hero-stat">
                    <strong>접속 제한</strong>
                    <span>허용 IP와 웹 세션을 함께 확인하므로 외부 직접 접근을 줄입니다.</span>
                </div>
            </div>
        </section>

        <section>
            <div class="section-head">
                <div>
                    <h2>도구 목록</h2>
                    <p>활성화된 도구만 바로 열 수 있고, 미설정 도구는 허브에서 상태를 확인할 수 있습니다.</p>
                </div>
            </div>

            <div class="grid">
                @foreach ($tools as $tool)
                    <article class="card">
                        <div class="card-header">
                            <span class="badge">{{ $tool['badge'] }}</span>
                            <span class="status {{ $tool['enabled'] ? '' : 'disabled' }}">
                                {{ $tool['enabled'] ? '사용 가능' : '미설정' }}
                            </span>
                        </div>

                        <div>
                            <h3>{{ $tool['name'] }}</h3>
                            <p>{{ $tool['description'] }}</p>
                        </div>

                        <div class="card-footer">
                            @if ($tool['enabled'])
                                <a class="tool-link" href="{{ $tool['url'] }}">열기</a>
                            @else
                                <span class="tool-disabled">준비 중</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="note">
                Swagger 링크는 환경변수 <code>INTERNAL_TOOL_SWAGGER_URL</code>로 제어합니다. 값이 없으면 허브에서는 미설정 상태로 표시합니다.
            </div>
        </section>
    </div>
</body>
</html>
