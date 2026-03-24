<?php

namespace App\Modules\Hospital\Http\Requests\HospitalVideo;

use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoCreateForHospitalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'doctor_id',
            'description',
            'distribution_channel',
            'publish_start_at',
            'publish_end_at',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
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
            'doctor_id' => ['nullable', 'integer', 'exists:hospital_doctors,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_usage_consented' => ['required', 'in:1'],
            'category_ids' => ['required', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'distribution_channel' => ['nullable', 'in:'.implode(',', [
                HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
                HospitalVideo::DISTRIBUTION_CHANNEL_APP,
            ])],
            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after_or_equal:publish_start_at'],
            'thumbnail_file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'video_file' => ['required', 'file', 'mimes:mp4,mov,avi,mkv,webm,m4v', 'max:512000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'doctor_id' => '의사',
            'title' => '제목',
            'description' => '설명',
            'is_usage_consented' => '영상 사용 동의 여부',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'distribution_channel' => '배포 채널',
            'publish_start_at' => '게시 시작 시각',
            'publish_end_at' => '게시 종료 시각',
            'thumbnail_file' => '썸네일 파일',
            'video_file' => '동영상 파일',
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
