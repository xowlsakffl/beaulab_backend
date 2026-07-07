<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalEntry;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalEntryUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_name' => ['required', 'string', 'max:100'],
            'hospital_phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'business_number' => ['required', 'string', 'max:30'],
            'business_registration_file' => ['nullable', 'file', 'max:10240'],
            'existing_business_registration_file_id' => ['nullable'],
            'ceo_name' => ['required', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_file' => ['nullable', 'file', 'max:10240'],
            'existing_license_file_id' => ['nullable'],
            'applicant_name' => ['required', 'string', 'max:50'],
            'applicant_position' => ['nullable', 'string', 'max:50'],
            'applicant_phone' => ['nullable', 'string', 'max:30'],
            'applicant_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_name' => '병의원명',
            'hospital_phone' => '전화번호',
            'address' => '주소',
            'address_detail' => '상세주소',
            'business_number' => '사업자등록번호',
            'business_registration_file' => '사업자등록증',
            'existing_business_registration_file_id' => '기존 사업자등록증',
            'ceo_name' => '대표자',
            'license_number' => '의사면허번호',
            'license_file' => '의사면허증',
            'existing_license_file_id' => '기존 의사면허증',
            'applicant_name' => '신청자 이름',
            'applicant_position' => '신청자 직책',
            'applicant_phone' => '신청자 전화번호',
            'applicant_email' => '신청자 이메일주소',
        ];
    }
}
