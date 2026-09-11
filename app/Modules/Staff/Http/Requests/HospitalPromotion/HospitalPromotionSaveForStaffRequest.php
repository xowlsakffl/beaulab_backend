<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalPromotionSaveForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $updating = $this->route('promotion') !== null;
        $presence = $updating ? 'sometimes' : 'required';

        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'content' => [$presence, 'required', 'string', 'max:2000000'],
            'side' => [$presence, 'required', Rule::in(HospitalPromotion::SIDES)],
            'slot' => [$presence, 'required', 'integer', 'between:1,3'],
            'start_date' => [$presence, 'required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'end_date' => [$presence, 'required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'status' => ['sometimes', 'required', Rule::in(HospitalPromotion::STATUSES)],
            'banner' => [$presence, 'required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'expected_updated_at' => $updating ? ['required', 'string', 'max:40', 'date_format:Y-m-d\TH:i:s.u\Z'] : ['prohibited'],
            'progress' => ['prohibited'],
            'view_count' => ['prohibited'],
            'click_count' => ['prohibited'],
            'created_by_staff_id' => ['prohibited'],
            'updated_by_staff_id' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '프로모션명', 'content' => '내용', 'side' => '게시위치', 'slot' => '노출순서',
            'start_date' => '게시 시작일', 'end_date' => '게시 종료일', 'status' => '공개여부',
            'banner' => '배너 이미지', 'expected_updated_at' => '수정 기준 시각',
        ];
    }
}
