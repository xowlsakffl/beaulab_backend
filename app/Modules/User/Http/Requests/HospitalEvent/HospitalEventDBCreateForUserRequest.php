<?php

namespace App\Modules\User\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventDBCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('hospital_doctor_id', $data) && $data['hospital_doctor_id'] === '') {
            $data['hospital_doctor_id'] = null;
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
            'hospital_doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(010-\d{4}-\d{4}|010\d{8})$/'],
            'contact_method' => ['required', Rule::in(HospitalEventDB::contactMethods())],
            'preferred_time' => ['required', Rule::in(HospitalEventDB::preferredTimes())],
            'privacy_agreed' => ['accepted'],
            'marketing_agreed' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_doctor_id' => '의료진',
            'name' => '이름',
            'phone' => '전화번호',
            'contact_method' => '연락수단',
            'preferred_time' => '선호시간',
            'privacy_agreed' => '개인정보 수집/이용 동의',
            'marketing_agreed' => '마케팅 수신 동의',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => '전화번호는 010-0000-0000 형식으로 입력해주세요.',
        ];
    }
}
