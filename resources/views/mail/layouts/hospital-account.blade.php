<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        @media only screen and (max-width: 600px) {
            .mail-outer { padding: 24px 16px !important; }
            .mail-content { padding: 24px 20px !important; }
        }
    </style>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;">
        <tr>
            <td align="center" class="mail-outer" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;table-layout:fixed;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
                    <tr>
                        <td class="mail-content" style="padding:32px;overflow-wrap:anywhere;">
                            <h1 style="margin:0 0 16px;font-size:20px;line-height:1.4;color:#111827;word-break:keep-all;">@yield('title')</h1>
                            @yield('content')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
