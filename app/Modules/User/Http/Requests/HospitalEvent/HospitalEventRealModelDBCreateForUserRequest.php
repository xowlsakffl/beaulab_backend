<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventRealModelDBCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('special_notes', $data)) {
            $data['special_notes'] = $this->normalizeToArray($data['special_notes']);
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
            'name' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', Rule::in(HospitalEventRealModelDB::genders())],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(010-\d{4}-\d{4}|010\d{8})$/'],
            'height_cm' => ['required', 'integer', 'min:1', 'max:300'],
            'weight_kg' => ['required', 'integer', 'min:1', 'max:500'],
            'surgery_period' => ['required', 'string', Rule::in(HospitalEventRealModelDB::surgeryPeriods())],
            'support_part' => ['required', 'string', 'max:100'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'blog_url' => ['nullable', 'url', 'max:255'],
            'special_notes' => ['nullable', 'array'],
            'special_notes.*' => ['string', 'distinct', Rule::in(HospitalEventRealModelDB::specialNotes())],
            'application_reason' => ['required', 'string', 'max:2000'],
            'inquiry' => ['nullable', 'string', 'max:2000'],
            'images' => ['required', 'array', 'min:1', 'max:'.HospitalEventRealModelDB::MAX_IMAGE_COUNT],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'gender' => '성별',
            'birth_date' => '생년월일',
            'phone' => '전화번호',
            'height_cm' => '키',
            'weight_kg' => '몸무게',
            'surgery_period' => '수술시기',
            'support_part' => '지원부위',
            'instagram_url' => '인스타그램 주소',
            'blog_url' => '블로그 주소',
            'special_notes' => '회원 특이사항',
            'special_notes.*' => '회원 특이사항',
            'application_reason' => '리얼모델 지원이유',
            'inquiry' => '문의사항',
            'images' => '신청 이미지 목록',
            'images.*' => '신청 이미지',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => '전화번호는 010-0000-0000 형식으로 입력해주세요.',
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeToArray(mixed $value): array
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
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }
}
