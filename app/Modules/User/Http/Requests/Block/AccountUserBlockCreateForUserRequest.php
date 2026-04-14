<?php

namespace App\Modules\User\Http\Requests\Block;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 앱 사용자 차단 생성 요청 검증 객체.
 * 실제 본인 차단 여부와 채팅 숨김 처리는 Domain Query에서 트랜잭션으로 검증한다.
 */
final class AccountUserBlockCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('blocked_user_id', $data) && $data['blocked_user_id'] === '') {
            $data['blocked_user_id'] = null;
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
            'blocked_user_id' => ['required', 'integer', 'exists:account_users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'blocked_user_id' => '차단할 사용자',
        ];
    }
}
