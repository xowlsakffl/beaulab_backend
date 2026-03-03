<?php


namespace App\Modules\Staff\Http\Requests\Beauty;

use Illuminate\Foundation\Http\FormRequest;

final class BeautyCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $businessNumber = $this->input('business_number');

        if (! is_string($businessNumber)) {
            return;
        }

        $normalizedBusinessNumber = preg_replace('/\D+/', '', $businessNumber);

        $this->merge([
            'business_number' => $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : $businessNumber,
        ]);
    }

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
                'unique:beauties,name',
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
            'business_number' => ['required', 'string', 'max:20', 'unique:beauty_business_registrations,business_number'],
            'company_name' => ['required', 'string', 'max:255'],
            'ceo_name' => ['required', 'string', 'max:100'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_item' => ['required', 'string', 'max:100'],
            'business_registration_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['date'],

            // 파일 필수
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['required', 'array', 'min:1', 'max:12'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '뷰티 업체명',
            'description' => '업체 소개',
            'consulting_hours' => '상담 가능 시간',
            'direction' => '찾아오는 길',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'tel' => '대표 번호',
            'email' => '대표 이메일',

            'business_number' => '사업자 등록번호',
            'company_name' => '상호명',
            'ceo_name' => '대표자',
            'business_type' => '업태',
            'business_item' => '종목',
            'business_registration_file' => '사업자등록증 파일',
            'business_address' => '사업장 주소',
            'business_address_detail' => '사업장 상세 주소',
            'issued_at' => '사업자 등록일',

            'logo' => '로고',
            'gallery' => '갤러리 이미지',
            'gallery.*' => '갤러리 이미지',
        ];
    }
}
