<?php

namespace App\Modules\Staff\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'department' => '부서',
            'job_title' => '직함/직무',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        // 빈 문자열 -> null (nullable 필드)
        foreach (['department', 'job_title'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
