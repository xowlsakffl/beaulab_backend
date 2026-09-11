@extends('mail.layouts.hospital-account')

@section('title', '이메일 인증번호')

@section('content')
    <p style="margin:0;font-size:14px;line-height:1.7;color:#6b7280;word-break:keep-all;">
        계정 생성 화면에 아래 인증번호를 입력해 주세요.
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding:28px 0 32px;">
                <p style="margin:0 0 12px;font-family:Arial,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;font-size:40px;font-weight:700;line-height:1.2;letter-spacing:0;color:#111827;">{{ $code }}</p>
                <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">
                    <span style="font-weight:700;color:#db477e;">{{ $expireMinutes }}분 이내</span>에 입력해 주세요.
                </p>
            </td>
        </tr>
        <tr>
            <td style="border-top:1px solid #e5e7eb;padding-top:20px;">
                <p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;word-break:keep-all;">
                    인증번호는 다른 사람에게 알려주지 마세요.<br>
                    요청하지 않으셨다면 이 메일을 무시해 주세요.
                </p>
            </td>
        </tr>
    </table>
@endsection
