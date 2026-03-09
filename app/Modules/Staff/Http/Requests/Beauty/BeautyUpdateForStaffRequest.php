<?php

namespace App\Modules\Staff\Http\Requests\Beauty;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BeautyUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        $nullableKeys = [
            'description',
            'consulting_hours',
            'direction',
            'address',
            'address_detail',
            'latitude',
            'longitude',
            'tel',
            'email',
            'business_number',
            'company_name',
            'ceo_name',
            'business_type',
            'business_item',
            'business_address',
            'business_address_detail',
        ];

        foreach ($nullableKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        if (isset($data['business_number']) && is_string($data['business_number'])) {
            $normalizedBusinessNumber = preg_replace('/\D+/', '', $data['business_number']);
            $data['business_number'] = $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : $data['business_number'];
        }

        if (array_key_exists('category_ids', $data)) {
            $data['category_ids'] = $this->normalizeIdList($data['category_ids']);
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
            // name은 변경 불가(수정 입력에서 제외)

            'description' => ['nullable', 'string', 'max:5000'],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],

            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],

            // 좌표 (DB는 string이지만 입력은 숫자 형태를 강제)
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'tel' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'business_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('beauty_business_registrations', 'business_number')->ignore($this->businessRegistrationId()),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'ceo_name' => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'business_item' => ['nullable', 'string', 'max:100'],
            'business_registration_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_BEAUTY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array', 'min:1', 'max:12'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    private function businessRegistrationId(): ?int
    {
        $beauty = $this->route('beauty');
        if (! $beauty instanceof Beauty) {
            return null;
        }
        return $beauty->businessRegistration()->value('id');
    }


    public function attributes(): array
    {
        return [
            'description' => '소개',
            'consulting_hours' => '진료 시간',
            'direction' => '오시는 길',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'tel' => '전화번호',
            'email' => '이메일',
            'business_number' => '사업자 등록번호',
            'company_name' => '상호명',
            'ceo_name' => '대표자명',
            'business_type' => '업태',
            'business_item' => '종목',
            'business_registration_file' => '사업자등록증 파일',
            'business_address' => '사업장 주소',
            'business_address_detail' => '사업장 상세 주소',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'logo' => '로고 이미지',
            'gallery' => '갤러리 이미지',
            'gallery.*' => '갤러리 이미지',
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
