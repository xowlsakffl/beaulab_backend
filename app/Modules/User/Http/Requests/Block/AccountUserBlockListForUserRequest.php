<?php

namespace App\Modules\User\Http\Requests\Block;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 앱 사용자 차단 목록 요청 검증 객체.
 * 현재는 페이지 크기만 받지만, 차단 검색 조건이 생기면 이 요청 객체에 확장한다.
 */
final class AccountUserBlockListForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('per_page', $data) && $data['per_page'] === '') {
            $data['per_page'] = null;
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => '페이지 크기',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
