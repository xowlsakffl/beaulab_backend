@extends('mail.layouts.hospital-account')

@section('title', '병의원 관리자 계정 생성 안내')

@section('content')
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">
        {{ $hospitalName }} 관리자 계정 생성 요청이 도착했습니다. 아래 버튼을 눌러 계정을 만들어 주세요.
    </p>
    <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#6b7280;">
        이 링크는 {{ $expireHours }}시간 동안 유효하며 한 번만 사용할 수 있습니다.
    </p>
    @include('mail.partials.hospital-account-link', ['url' => $invitationUrl, 'label' => '계정 생성하기'])
@endsection
