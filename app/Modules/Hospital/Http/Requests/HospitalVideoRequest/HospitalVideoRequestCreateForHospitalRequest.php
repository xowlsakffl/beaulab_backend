<?php

namespace App\Modules\Hospital\Http\Requests\HospitalVideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalVideoRequestCreateForHospitalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('requested_publish_start_at', $data) && ! array_key_exists('publish_start_at', $data)) {
            $data['publish_start_at'] = $data['requested_publish_start_at'];
        }

        if (array_key_exists('requested_publish_end_at', $data) && ! array_key_exists('publish_end_at', $data)) {
            $data['publish_end_at'] = $data['requested_publish_end_at'];
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
            'is_usage_consented' => ['required', 'boolean'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_publish_period_unlimited' => ['required', 'boolean'],
            'source_video_file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:307200'],
            'source_thumbnail_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'doctor_id' => '의사 정보',
            'title' => '제목',
            'description' => '설명',
            'is_usage_consented' => '사용 동의 여부',
            'duration_seconds' => '영상 길이(초)',
            'publish_start_at' => '게시 시작 요청일',
            'publish_end_at' => '게시 종료 요청일',
            'is_publish_period_unlimited' => '게시 기간 무제한 여부',
            'source_video_file' => '원본 동영상 파일',
            'source_thumbnail_file' => '썸네일 파일',
        ];
    }
}
