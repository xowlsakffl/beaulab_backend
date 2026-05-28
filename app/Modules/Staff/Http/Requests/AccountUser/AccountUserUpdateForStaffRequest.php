<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AccountUserUpdateForStaffRequest 역할 정의.
 * 일반 회원 계정 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AccountUserUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('name', $data) && $data['name'] === '') {
            $data['name'] = null;
        }

        if (array_key_exists('nickname', $data) && is_string($data['nickname'])) {
            $data['nickname'] = trim($data['nickname']);
        }

        if (array_key_exists('phone', $data) && is_string($data['phone'])) {
            $data['phone'] = trim($data['phone']);
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('account_users', 'nickname')->ignore($this->route('user')?->getKey()),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['sometimes', 'required', 'in:ACTIVE,SUSPENDED,BLOCKED'],
            'comment_notification_enabled' => ['sometimes', 'boolean'],
            'note_notification_enabled' => ['sometimes', 'boolean'],
            'marketing_sms_agreed' => ['sometimes', 'boolean'],
            'marketing_email_agreed' => ['sometimes', 'boolean'],
            'marketing_push_agreed' => ['sometimes', 'boolean'],
            'marketing_night_push_agreed' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '실명',
            'nickname' => '닉네임',
            'phone' => '전화번호',
            'status' => '운영 상태',
            'comment_notification_enabled' => '댓글 알림 동의 여부',
            'note_notification_enabled' => '쪽지 알림 동의 여부',
            'marketing_sms_agreed' => '마케팅 SMS 수신 동의 여부',
            'marketing_email_agreed' => '마케팅 이메일 수신 동의 여부',
            'marketing_push_agreed' => '마케팅 푸시 수신 동의 여부',
            'marketing_night_push_agreed' => '마케팅 야간푸시 수신 동의 여부',
        ];
    }
}
