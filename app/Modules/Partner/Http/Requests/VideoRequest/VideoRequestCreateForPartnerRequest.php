<?php

namespace App\Modules\Partner\Http\Requests\VideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class VideoRequestCreateForPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'beauty_id' => ['nullable', 'integer', 'exists:beauties,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'expert_id' => ['nullable', 'integer', 'exists:experts,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_usage_consented' => ['required', 'accepted'],
            'source_video_file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm', 'max:204800'],
            'source_thumbnail_file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'requested_publish_start_at' => ['nullable', 'date'],
            'requested_publish_end_at' => ['nullable', 'date', 'after_or_equal:requested_publish_start_at'],
            'is_publish_period_unlimited' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병원 ID',
            'beauty_id' => '뷰티업체 ID',
            'doctor_id' => '의사 ID',
            'expert_id' => '뷰티전문가 ID',
            'title' => '제목',
            'description' => '설명',
            'is_usage_consented' => '영상 활용 동의 여부',
            'source_video_file' => '원본 동영상 파일',
            'source_thumbnail_file' => '원본 썸네일 파일',
            'duration_seconds' => '재생 시간(초)',
            'requested_publish_start_at' => '게시 시작 시각',
            'requested_publish_end_at' => '게시 종료 시각',
            'is_publish_period_unlimited' => '무기한 게시 여부',
        ];
    }
}
