<?php

namespace App\Modules\Staff\Http\Requests\BannerExposure;

use App\Domains\BannerExposure\Models\BannerExposure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BannerExposureTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['placement' => ['sometimes', 'required', Rule::in(BannerExposure::PLACEMENTS)],
            'target_type' => ['required', Rule::in(['CATEGORY', 'HOSPITAL', 'EVENT', 'NOTICE'])],
            'category_id' => ['nullable', 'integer', 'min:1'], 'q' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']];
    }
}
