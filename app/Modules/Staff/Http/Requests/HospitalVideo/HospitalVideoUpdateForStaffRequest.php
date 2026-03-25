<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\Common\Models\Category\Category;
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
            'title',
            'description',
            'distribution_channel',
            'external_video_id',
            'external_video_url',
            'duration_seconds',
            'status',
            'allow_status',
            'publish_start_at',
            'publish_end_at',
            'is_publish_period_unlimited',
            'remove_video_file',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        if (empty($data['external_video_id']) && is_string($data['external_video_url'] ?? null)) {
            $extractedVideoId = $this->extractYoutubeVideoId($data['external_video_url']);
            if ($extractedVideoId !== null) {
                $data['external_video_id'] = $extractedVideoId;
            }
        }

        if (array_key_exists('category_ids', $data)) {
            $data['category_ids'] = $this->normalizeIdList($data['category_ids']);
        }

        foreach (['is_publish_period_unlimited', 'remove_video_file'] as $booleanKey) {
            if (array_key_exists($booleanKey, $data) && $data[$booleanKey] !== null) {
                $data[$booleanKey] = filter_var($data[$booleanKey], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
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
        return [
            'hospital_id' => ['sometimes', 'nullable', 'integer', 'exists:hospitals,id'],
            'doctor_id' => ['sometimes', 'nullable', 'integer', 'exists:hospital_doctors,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'distribution_channel' => ['sometimes', 'nullable', 'in:'.implode(',', [
                HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
                HospitalVideo::DISTRIBUTION_CHANNEL_APP,
            ])],
            'external_video_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'external_video_url' => ['sometimes', 'nullable', 'url', 'max:1024'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'nullable', 'in:ACTIVE,INACTIVE'],
            'allow_status' => ['sometimes', 'nullable', 'in:SUBMITTED,IN_REVIEW,APPROVED,REJECTED,EXCLUDED,PARTNER_CANCELED'],
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'publish_start_at' => ['sometimes', 'nullable', 'date'],
            'publish_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_publish_period_unlimited' => ['sometimes', 'nullable', 'boolean'],
            'remove_video_file' => ['sometimes', 'nullable', 'boolean'],
            'thumbnail_file' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병원',
            'doctor_id' => '의사',
            'title' => '제목',
            'description' => '설명',
            'distribution_channel' => '배포 채널',
            'external_video_id' => '외부 영상 ID',
            'external_video_url' => '외부 영상 URL',
            'duration_seconds' => '재생 시간(초)',
            'status' => '운영 상태',
            'allow_status' => '검수 상태',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'publish_start_at' => '게시 시작 시각',
            'publish_end_at' => '게시 종료 시각',
            'is_publish_period_unlimited' => '무기한 게시 여부',
            'remove_video_file' => '원본 동영상 파일 삭제 여부',
            'thumbnail_file' => '썸네일 파일',
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

    private function extractYoutubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($host === 'youtu.be' && $path !== '') {
            return $path;
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            if (is_string($query['v'] ?? null) && $query['v'] !== '') {
                return $query['v'];
            }

            if (str_starts_with($path, 'shorts/')) {
                $shortId = trim(substr($path, strlen('shorts/')));
                return $shortId !== '' ? $shortId : null;
            }
        }

        return null;
    }
}
