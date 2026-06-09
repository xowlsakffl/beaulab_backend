<?php

namespace App\Modules\Staff\Http\Requests\HospitalDoctor;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * DoctorUpdateForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalDoctorUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'hospital_id',
            'gender',
            'position',
            'career_started_at',
            'license_number',
            'specialist_field',
            'existing_profile_image_id',
            'existing_license_image_id',
            'existing_specialist_certificate_image_id',
        ] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        if (array_key_exists('specialist_field', $data) && $data['specialist_field'] === null) {
            $data['specialist_field'] = HospitalDoctor::SPECIALIST_FIELD_NONE;
        }

        if (array_key_exists('position', $data)) {
            $data['position'] = HospitalDoctor::normalizePosition($data['position']);
        }

        if (array_key_exists('gender', $data)) {
            $data['gender'] = HospitalDoctor::normalizeGender($data['gender']);
        }

        foreach (['educations', 'careers', 'etc_contents'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$key] = $decoded;
                }
            }
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
            'hospital_id' => ['sometimes', 'required', 'integer', 'exists:hospitals,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in([HospitalDoctor::GENDER_MALE, HospitalDoctor::GENDER_FEMALE])],
            'position' => ['nullable', Rule::in(HospitalDoctor::positions())],
            'career_started_at' => ['nullable', 'date'],
            'license_number' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^\d+$/'],
            'specialist_field' => ['nullable', Rule::in(HospitalDoctor::specialistFields())],
            'educations' => ['nullable', 'array', 'max:20'],
            'careers' => ['nullable', 'array', 'max:20'],
            'etc_contents' => ['nullable', 'array', 'max:20'],
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT, Category::DOMAIN_HOSPITAL_REVIEW_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'status' => ['nullable', 'in:ACTIVE,SUSPENDED,INACTIVE'],
            'allow_status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],

            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=1/1'],
            'license_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'specialist_certificate_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'existing_profile_image_id' => ['sometimes', 'nullable', 'integer', $this->mediaBelongsToDoctorRule('profile_image')],
            'existing_license_image_id' => ['sometimes', 'nullable', 'integer', $this->mediaBelongsToDoctorRule('license_image')],
            'existing_specialist_certificate_image_id' => ['sometimes', 'nullable', 'integer', $this->mediaBelongsToDoctorRule('specialist_certificate_image')],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.max' => '5MB 이하의 파일만 업로드 가능합니다.',
            'profile_image.dimensions' => '1:1비율의 이미지로 업로드 가능합니다.',
            'license_number.required' => '의사면허 번호를 입력해 주세요.',
            'license_number.regex' => '의사면허 번호는 숫자만 입력할 수 있습니다.',
            'educations.max' => '학력사항은 최대 20개까지 입력할 수 있습니다.',
            'careers.max' => '경력사항은 최대 20개까지 입력할 수 있습니다.',
            'etc_contents.max' => '활동사항은 최대 20개까지 입력할 수 있습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => "병원 ID",
            'sort_order' => "정렬 순서",
            'name' => '의사명',
            'gender' => '성별',
            'position' => "직책",
            'career_started_at' => '경력 시작일',
            'license_number' => '면허증 번호',
            'specialist_field' => '전문의 분류',
            'educations' => '학력 사항',
            'careers' => '경력 사항',
            'etc_contents' => '기타 사항',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'status' => '운영 상태',
            'allow_status' => '검수 상태',

            'profile_image' => '프로필 이미지',
            'license_image' => '면허증 이미지',

            'specialist_certificate_image' => '전문의 면허증 이미지',
            'existing_profile_image_id' => '기존 프로필 이미지',
            'existing_license_image_id' => '기존 면허증 이미지',
            'existing_specialist_certificate_image_id' => '기존 전문의 증명서 이미지',
        ];
    }

    private function mediaBelongsToDoctorRule(string $collection): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($collection): void {
            if ($value === null || $value === '') {
                return;
            }

            $doctor = $this->route('doctor');

            if (! $doctor instanceof HospitalDoctor) {
                $fail('의료진 정보를 확인할 수 없습니다.');
                return;
            }

            $exists = Media::query()
                ->whereKey((int) $value)
                ->where('model_type', HospitalDoctor::class)
                ->where('model_id', $doctor->getKey())
                ->where('collection', $collection)
                ->exists();

            if (! $exists) {
                $fail('선택한 기존 파일 정보가 올바르지 않습니다.');
            }
        };
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
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            $value = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
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
