<?php


namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $businessNumber = $this->input('business_number');
        $categoryIds = $this->normalizeIdList($this->input('category_ids'));

        $mergePayload = [];

        if (is_string($businessNumber)) {
            $normalizedBusinessNumber = preg_replace('/\D+/', '', $businessNumber);

            $mergePayload['business_number'] = $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : $businessNumber;
        }

        if ($this->has('category_ids')) {
            $mergePayload['category_ids'] = array_values(array_unique($categoryIds));
        }

        if ($mergePayload !== []) {
            $this->merge($mergePayload);
        }
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
            'allow_status' => ['required', Rule::in([Hospital::ALLOW_PENDING, Hospital::ALLOW_APPROVED, Hospital::ALLOW_REJECTED])],
            'status' => ['required', Rule::in([Hospital::STATUS_ACTIVE, Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN])],

            /**
             * 사업자 정보
             **/
            // 사업자 등록 필수
            'business_number' => ['required', 'string', 'max:20', 'unique:hospital_business_registrations,business_number'],
            'company_name' => ['required', 'string', 'max:255'],
            'ceo_name' => ['required', 'string', 'max:100'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_item' => ['required', 'string', 'max:100'],
            'business_registration_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],

            // 카테고리(단일 입력)
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],

            // 파일 필수
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['required', 'array', 'min:1', 'max:12'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '병원명',
            'description' => '병원 소개',
            'consulting_hours' => '상담 가능 시간',
            'direction' => '찾아오는 길',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'tel' => '대표 번호',
            'email' => '대표 이메일',
            'allow_status' => '검수 상태',
            'status' => '운영 상태',

            'business_number' => '사업자 등록번호',
            'company_name' => '상호명',
            'ceo_name' => '대표자',
            'business_type' => '업태',
            'business_item' => '종목',
            'business_registration_file' => '사업자등록증 파일',
            'business_address' => '사업장 주소',
            'business_address_detail' => '사업장 상세 주소',
            'issued_at' => '사업자 등록일',

            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',

            'logo' => '로고',
            'gallery' => '대표/내부 이미지',
            'gallery.*' => '대표/내부 이미지',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->values()
            ->all();
    }
}
