<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'doctor_id',
            'manager_staff_id',
            'description',
            'external_video_url',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        foreach (['category_ids', 'hashtag_ids'] as $listKey) {
            if (array_key_exists($listKey, $data)) {
                $data[$listKey] = $this->normalizeIdList($data[$listKey]);
            }
        }

        if (array_key_exists('hashtag_names', $data)) {
            $data['hashtag_names'] = $this->normalizeNameList($data['hashtag_names']);
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
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:hospital_doctors,id'],
            'manager_staff_id' => ['nullable', 'integer', 'exists:account_staffs,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'external_video_url' => ['required', 'url', 'max:1024'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'category_ids' => ['nullable', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY);
                }),
            ],
            'hashtag_ids' => ['nullable', 'array', 'max:30'],
            'hashtag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hashtags', 'id')->where(static fn ($query) => $query
                    ->where('status', Hashtag::STATUS_ACTIVE)),
            ],
            'hashtag_names' => ['nullable', 'array', 'max:30'],
            'hashtag_names.*' => ['string', 'distinct', 'max:'.Hashtag::NAME_MAX_LENGTH, 'regex:'.Hashtag::VALID_NAME_REGEX],
            'thumbnail_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'thumbnail_file.image' => '썸네일은 이미지 파일만 업로드할 수 있습니다.',
            'thumbnail_file.mimes' => '썸네일은 jpg, jpeg, png 파일만 업로드할 수 있습니다.',
            'thumbnail_file.max' => '썸네일은 최대 5MB 이하로 업로드해 주세요.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'doctor_id' => '의료진',
            'manager_staff_id' => '담당자',
            'title' => '동영상 제목',
            'description' => '영상 설명',
            'external_video_url' => '유튜브 링크',
            'duration_seconds' => '재생시간',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'hashtag_ids' => '해시태그 목록',
            'hashtag_ids.*' => '해시태그',
            'hashtag_names' => '신규 해시태그 목록',
            'hashtag_names.*' => '신규 해시태그',
            'thumbnail_file' => '썸네일',
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

    /**
     * @return array<int, string>
     */
    private function normalizeNameList(mixed $value): array
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
            ->filter(static fn ($item): bool => is_string($item) || is_numeric($item))
            ->map(static fn ($item): string => Hashtag::sanitizeName((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique(static fn (string $item): string => Hashtag::normalizeName($item))
            ->values()
            ->all();
    }
}
