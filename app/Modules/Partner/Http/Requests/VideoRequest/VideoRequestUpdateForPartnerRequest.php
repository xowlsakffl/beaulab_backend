<?php

namespace App\Modules\Partner\Http\Requests\VideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class VideoRequestUpdateForPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['sometimes', 'nullable', 'integer', 'exists:hospitals,id'],
            'beauty_id' => ['sometimes', 'nullable', 'integer', 'exists:beauties,id'],
            'doctor_id' => ['sometimes', 'nullable', 'integer', 'exists:doctors,id'],
            'expert_id' => ['sometimes', 'nullable', 'integer', 'exists:experts,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_usage_consented' => ['sometimes', 'boolean'],
            'source_video_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'source_thumbnail_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'duration_seconds' => ['sometimes', 'integer', 'min:0'],
            'requested_publish_start_at' => ['sometimes', 'nullable', 'date'],
            'requested_publish_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:requested_publish_start_at'],
            'is_publish_period_unlimited' => ['sometimes', 'boolean'],
        ];
    }
}
