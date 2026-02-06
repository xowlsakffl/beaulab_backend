<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'required' => ':attribute 항목은 필수입니다.',
    'string' => ':attribute 항목은 문자열이어야 합니다.',
    'email' => ':attribute 형식이 올바르지 않습니다.',
    'min' => [
        'string' => ':attribute 항목은 최소 :min자 이상이어야 합니다.',
        'numeric' => ':attribute 항목은 최소 :min 이상이어야 합니다.',
        'file' => ':attribute 항목은 최소 :minKB 이상이어야 합니다.',
        'array' => ':attribute 항목은 최소 :min개 이상이어야 합니다.',
    ],
    'max' => [
        'string' => ':attribute 항목은 최대 :max자 이하여야 합니다.',
        'numeric' => ':attribute 항목은 최대 :max 이하여야 합니다.',
        'file' => ':attribute 항목은 최대 :maxKB 이하여야 합니다.',
        'array' => ':attribute 항목은 최대 :max개 이하여야 합니다.',
    ],
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'unique' => '이미 사용 중인 :attribute 입니다.',
    'exists' => '선택한 :attribute 값이 올바르지 않습니다.',
    'in' => '선택한 :attribute 값이 올바르지 않습니다.',
    'boolean' => ':attribute 항목은 true/false 값이어야 합니다.',

    // Fortify/설정 화면에서 자주 뜨는 것들
    'current_password' => '현재 비밀번호가 올바르지 않습니다.',

    // Laravel 기본 password rule 메시지들
    'password' => [
        'letters' => ':attribute 항목은 최소 한 개의 영문자를 포함해야 합니다.',
        'mixed' => ':attribute 항목은 대문자와 소문자를 각각 최소 한 개 이상 포함해야 합니다.',
        'numbers' => ':attribute 항목은 최소 한 개의 숫자를 포함해야 합니다.',
        'symbols' => ':attribute 항목은 최소 한 개의 특수문자를 포함해야 합니다.',
        'uncompromised' => '입력한 :attribute 는 유출된 적이 있습니다. 다른 :attribute 를 사용해주세요.',
    ],
    'regex' => ':attribute 항목의 형식이 올바르지 않습니다.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    | 여기로 "nickname / email / password" 같은 필드명을 한글로 바꾸면
    | Inertia props의 errors 키가 그대로여도 메시지는 한글로 예쁘게 뜸
    */

    'attributes' => [
        // 로그인 폼 기준
        'email' => '이메일',
        'password' => '비밀번호',
        'nickname' => '아이디',

        // Fortify/Settings에서 흔한 것들
        'name' => '이름',
        'current_password' => '현재 비밀번호',
        'password_confirmation' => '비밀번호 확인',
    ],
];
