<?php


namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalCreateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * 병원 정보
             **/
            // 필수
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:hospitals,name',
            ],

            // 소개/텍스트
            'description' => ['nullable', 'string', 'max:5000'],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],

            // 주소
            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],

            // 좌표
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // 연락처
            'tel' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],


            /**
             * 사업자 정보
             **/
            // 사업자 등록 필수
            'business_number' => ['required', 'string', 'max:20', 'unique:business_registrations,business_number'],
            'company_name' => ['required', 'string', 'max:255'],
            'ceo_name' => ['required', 'string', 'max:100'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_item' => ['required', 'string', 'max:100'],
            'business_registration_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],

            // 병원 회원(owner) 계정 필수
            'owner_email' => ['required', 'email:rfc,dns', 'max:255', 'unique:account_partners,email'],
            'owner_nickname' => ['required', 'string', 'max:255', 'unique:account_partners,nickname'],
            'owner_password' => ['required', 'string', 'min:8', 'max:255'],

            // 파일
            // 파일 필수
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'representative_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'interior_images' => ['required', 'array', 'min:1', 'max:12'],
            'interior_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '병원명',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'tel' => '대표 번호',
            'email' => '대표 이메일',

            'business_number' => '사업자 등록번호',
            'company_name' => '상호명',
            'ceo_name' => '대표자',
            'business_type' => '업태',
            'business_item' => '종목',
            'business_registration_certificate' => '사업자등록증 파일',
            'owner_email' => '병원 소유주 이메일',
            'owner_nickname' => '병원 소유주 아이디',
            'owner_password' => '병원 소유주 비밀번호',

            'logo' => '로고',
            'representative_image' => '대표 이미지',
            'interior_images' => '내부 이미지',
            'interior_images.*' => '내부 이미지',
        ];
    }
}
