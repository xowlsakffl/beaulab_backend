<?php

namespace App\Modules\Staff\Http\Requests\BannerExposure;

use App\Domains\BannerExposure\Models\BannerExposure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BannerExposureSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $updating = $this->route('bannerExposure') !== null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'placement' => [$updating ? 'prohibited' : 'required', Rule::in(BannerExposure::PLACEMENTS)],
            'connection_type' => ['required', Rule::in(['EXHIBITION', 'PAGE'])],
            'target_type' => ['required', Rule::in(['HOSPITAL', 'EVENT', 'NOTICE', 'EXTERNAL'])],
            'target_id' => ['nullable', 'required_unless:target_type,EXTERNAL', 'integer', 'min:1'],
            'category_id' => ['nullable', 'required_if:connection_type,EXHIBITION', 'integer', 'min:1'],
            'external_url' => ['nullable', 'required_if:target_type,EXTERNAL', 'url:https', 'max:2048'],
            'open_mode' => ['required', Rule::in(['IN_APP', 'BROWSER'])],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'banner' => [$updating ? 'sometimes' : 'required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'expected_updated_at' => $updating ? ['required', 'date_format:Y-m-d\TH:i:s.u\Z'] : ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => '배너명', 'placement' => '게시위치', 'connection_type' => '연결유형', 'target_type' => '연결 대상 유형', 'target_id' => '연결 대상', 'category_id' => '기획전', 'external_url' => '외부 링크', 'start_at' => '게시 시작일시', 'end_at' => '게시 종료일시', 'banner' => '배너 이미지', 'status' => '공개여부'];
    }
}
