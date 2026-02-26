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
            'is_usage_consented' => ['required', 'boolean'],
            'source_video_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'source_thumbnail_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'requested_publish_start_at' => ['nullable', 'date'],
            'requested_publish_end_at' => ['nullable', 'date', 'after_or_equal:requested_publish_start_at'],
            'is_publish_period_unlimited' => ['nullable', 'boolean'],
        ];
    }
}
