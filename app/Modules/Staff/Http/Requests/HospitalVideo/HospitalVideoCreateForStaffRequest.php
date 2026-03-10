<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalVideoCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'doctor_id',
            'description',
            'distribution_channel',
            'external_video_id',
            'external_video_url',
            'duration_seconds',
            'status',
            'published_at',
            'publish_start_at',
            'publish_end_at',
            'is_publish_period_unlimited',
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'distribution_channel' => ['nullable', 'in:YOUTUBE'],
            'external_video_id' => ['nullable', 'string', 'max:191', 'required_without:external_video_url'],
            'external_video_url' => ['nullable', 'url', 'max:1024', 'required_without:external_video_id'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:ACTIVE,SUSPENDED,PRIVATE'],
            'published_at' => ['nullable', 'date'],
            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_publish_period_unlimited' => ['nullable', 'boolean'],
            'thumbnail_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => 'hospital',
            'doctor_id' => 'doctor',
            'title' => 'title',
            'description' => 'description',
            'distribution_channel' => 'distribution channel',
            'external_video_id' => 'external video id',
            'external_video_url' => 'external video url',
            'duration_seconds' => 'duration seconds',
            'status' => 'status',
            'published_at' => 'published at',
            'publish_start_at' => 'publish start at',
            'publish_end_at' => 'publish end at',
            'is_publish_period_unlimited' => 'is publish period unlimited',
            'thumbnail_file' => 'thumbnail file',
        ];
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
