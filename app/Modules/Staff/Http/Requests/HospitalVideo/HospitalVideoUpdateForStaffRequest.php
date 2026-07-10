<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'hospital_id',
            'doctor_id',
            'manager_staff_id',
            'title',
            'description',
            'external_video_url',
            'existing_thumbnail_file_id',
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
            'hospital_id' => ['sometimes', 'nullable', 'integer', 'exists:hospitals,id'],
            'doctor_id' => ['sometimes', 'nullable', 'integer', 'exists:hospital_doctors,id'],
            'manager_staff_id' => ['sometimes', 'nullable', 'integer', 'exists:account_staffs,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'external_video_url' => ['sometimes', 'nullable', 'url', 'max:1024'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:86400'],
            'view_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'like_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY);
                }),
            ],
            'hashtag_ids' => ['sometimes', 'array', 'max:30'],
            'hashtag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hashtags', 'id')->where(static fn ($query) => $query
                    ->where('status', Hashtag::STATUS_ACTIVE)),
            ],
            'hashtag_names' => ['sometimes', 'array', 'max:30'],
            'hashtag_names.*' => ['string', 'distinct', 'max:'.Hashtag::NAME_MAX_LENGTH, 'regex:'.Hashtag::VALID_NAME_REGEX],
            'existing_thumbnail_file_id' => ['sometimes', 'nullable', 'integer', $this->mediaBelongsToVideoRule('thumbnail_file')],
            'thumbnail_file' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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
            'view_count' => '조회수',
            'like_count' => '좋아요수',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'hashtag_ids' => '해시태그 목록',
            'hashtag_ids.*' => '해시태그',
            'hashtag_names' => '신규 해시태그 목록',
            'hashtag_names.*' => '신규 해시태그',
            'existing_thumbnail_file_id' => '기존 썸네일',
            'thumbnail_file' => '썸네일',
        ];
    }

    private function mediaBelongsToVideoRule(string $collection): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($collection): void {
            if ($value === null || $value === '') {
                return;
            }

            $video = $this->route('video');

            if (! $video instanceof HospitalVideo) {
                $fail('동영상 정보를 확인할 수 없습니다.');

                return;
            }

            $exists = Media::query()
                ->whereKey((int) $value)
                ->where('model_type', HospitalVideo::class)
                ->where('model_id', $video->getKey())
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
