<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>비밀번호 재설정</title>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:32px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 16px;font-size:20px;line-height:1.4;color:#111827;">비밀번호 재설정 안내</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">
                                {{ $actorLabel }} 비밀번호 재설정을 요청하셨습니다. 아래 버튼을 눌러 새 비밀번호를 설정해 주세요.
                            </p>
                            <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#6b7280;">
                                이 링크는 {{ $expireMinutes }}분 동안만 유효합니다. 본인이 요청하지 않았다면 이 메일을 무시해 주세요.
                            </p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $resetUrl }}" style="display:inline-block;border-radius:8px;background:#f06292;padding:12px 18px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                                    비밀번호 재설정
                                </a>
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#9ca3af;word-break:break-all;">
                                버튼이 동작하지 않으면 아래 주소를 브라우저에 붙여 넣어 주세요.<br>
                                {{ $resetUrl }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
