<?php

namespace App\Modules\Staff\Http\Requests\BannerExposure;

use App\Domains\BannerExposure\Models\BannerExposure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BannerExposureListRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('connection_type');
        if (is_string($value)) {
            $this->merge(['connection_type' => array_values(array_filter(array_map('trim', explode(',', $value))))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placement' => ['required', Rule::in(BannerExposure::PLACEMENTS)],
            'connection_type' => ['nullable', 'array', 'max:3'],
            'connection_type.*' => ['string', 'distinct', Rule::in(['AD', 'EXHIBITION', 'PAGE'])],
            'q' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
