<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['category_ids', 'doctor_ids'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->normalizeIdList($data[$key]);
            }
        }

        foreach (['doctor_assignments', 'options', 'procedure_targets', 'procedure_benefits'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->normalizeJsonArray($data[$key]);
            }
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = $this->normalizeOptions($data['options']);
        }

        if (! array_key_exists('primary_category_id', $data) && array_key_exists('representative_category_id', $data)) {
            $data['primary_category_id'] = $data['representative_category_id'];
        }

        foreach (['normal_price', 'event_price', 'consultation_price'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $data[$key] = str_replace(',', '', $data[$key]);
            }
        }

        foreach (['event_end_at', 'side_effect_notice'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedTextPattern = '/^[\pL\pN\s\.,\-・_:()\[\]&°+\/。"“”]+$/u';

        return [
            'hospital_id' => ['sometimes', 'integer', Rule::exists('hospitals', 'id')->whereNull('deleted_at')],
            'event_type' => ['sometimes', Rule::in(HospitalEvent::types())],
            'is_male_targeted' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:20', "regex:{$allowedTextPattern}"],
            'description' => ['sometimes', 'string', 'max:40', "regex:{$allowedTextPattern}"],
            'is_event_period_unlimited' => ['sometimes', 'boolean'],
            'event_start_at' => ['sometimes', 'date'],
            'event_end_at' => [Rule::requiredIf(fn (): bool => $this->has('is_event_period_unlimited') && ! $this->boolean('is_event_period_unlimited')), 'nullable', 'date', 'after_or_equal:event_start_at'],
            'normal_price' => ['sometimes', 'integer', 'min:0'],
            'event_price' => ['sometimes', 'integer', 'min:0'],
            'is_vat_included' => ['sometimes', 'boolean'],
            'consultation_price' => ['nullable', 'integer', 'min:0'],
            'has_options' => ['nullable', 'boolean'],
            'side_effect_notice' => ['nullable', 'string', 'max:90'],
            'allow_status' => ['nullable', Rule::in(HospitalEvent::allowStatuses())],
            'hospital_status' => ['nullable', Rule::in(HospitalEvent::hospitalStatuses())],
            'admin_status' => ['nullable', Rule::in(HospitalEvent::adminStatuses())],

            'category_ids' => ['sometimes', 'array', 'min:1', 'max:3'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => Category::constrainActiveLeafHospitalMedicalCategory($query)),
            ],
            'primary_category_id' => [
                'required_with:category_ids',
                'integer',
                Rule::exists('categories', 'id')->where(static fn ($query) => Category::constrainActiveLeafHospitalMedicalCategory($query)),
            ],

            'doctor_ids' => ['nullable', 'array', 'max:3'],
            'doctor_ids.*' => ['integer', 'distinct', Rule::exists('hospital_doctors', 'id')->whereNull('deleted_at')],
            'doctor_assignments' => ['nullable', 'array', 'max:3'],
            'doctor_assignments.*.id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->whereNull('deleted_at')],
            'doctor_assignments.*.hospital_doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->whereNull('deleted_at')],
            'doctor_assignments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'doctor_assignments.*.is_career_visible' => ['nullable', 'boolean'],
            'doctor_assignments.*.is_activity_visible' => ['nullable', 'boolean'],

            'options' => ['nullable', 'array', 'max:50'],
            'options.*.name' => ['required_with:options', 'string', 'max:40'],
            'options.*.session_count' => ['nullable', 'integer', 'min:1'],
            'options.*.normal_price' => ['nullable', 'integer', 'min:0'],
            'options.*.event_price' => ['nullable', 'integer', 'min:0'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'procedure_targets' => ['nullable', 'array', 'min:1', 'max:5'],
            'procedure_targets.*' => ['string', 'max:90'],
            'procedure_benefits' => ['nullable', 'array', 'min:1', 'max:6'],
            'procedure_benefits.*' => ['string', 'max:90'],

            'thumbnail_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'event_page_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => '이벤트명에 사용할 수 없는 특수문자 또는 이모티콘이 포함되어 있습니다.',
            'description.regex' => '이벤트 설명에 사용할 수 없는 특수문자 또는 이모티콘이 포함되어 있습니다.',
            'thumbnail_image.max' => '썸네일 이미지는 최대 2MB 이하만 업로드할 수 있습니다.',
            'event_page_image.max' => '이벤트 페이지 이미지는 최대 5MB 이하만 업로드할 수 있습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'event_type' => '이벤트 유형',
            'is_male_targeted' => '남자성형 이벤트 여부',
            'name' => '이벤트명',
            'description' => '이벤트 설명',
            'is_event_period_unlimited' => '이벤트 기간 무제한 여부',
            'event_start_at' => '이벤트 시작일',
            'event_end_at' => '이벤트 종료일',
            'normal_price' => '정상 가격',
            'event_price' => '이벤트 가격',
            'is_vat_included' => 'VAT 포함 여부',
            'consultation_price' => '상담 신청 단가',
            'has_options' => '이벤트 옵션 사용 여부',
            'side_effect_notice' => '부작용 안내',
            'allow_status' => '검수 상태',
            'hospital_status' => '공개여부',
            'admin_status' => '강제중지 상태',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'primary_category_id' => '대표 카테고리',
            'doctor_ids' => '의료진 목록',
            'doctor_ids.*' => '의료진',
            'doctor_assignments' => '의료진 선택 목록',
            'options' => '이벤트 옵션 목록',
            'procedure_targets' => '시술 대상',
            'procedure_benefits' => '시술 장점',
            'thumbnail_image' => '썸네일',
            'event_page_image' => '이벤트 페이지',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        $value = $this->normalizeJsonArray($value);

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeJsonArray(mixed $value): array
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

        return is_array($value) ? $value : [];
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->map(static function (mixed $option): mixed {
                if (! is_array($option)) {
                    return $option;
                }

                foreach (['normal_price', 'event_price'] as $key) {
                    if (array_key_exists($key, $option) && is_string($option[$key])) {
                        $option[$key] = str_replace(',', '', $option[$key]);
                    }
                }

                return $option;
            })
            ->values()
            ->all();
    }
}
