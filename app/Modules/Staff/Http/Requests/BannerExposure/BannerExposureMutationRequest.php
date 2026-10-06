<?php

namespace App\Modules\Staff\Http\Requests\BannerExposure;

use App\Domains\BannerExposure\Models\BannerExposure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BannerExposureMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('staff.banner-exposures.reorder')) {
            return ['placement' => ['required', Rule::in(BannerExposure::PLACEMENTS)], 'revision' => ['required', 'integer', 'min:0'],
                'keys' => ['required', 'array', 'min:1', 'max:100'], 'keys.*' => ['required', 'string', 'distinct', 'regex:/^(ad|banner):[1-9][0-9]*$/']];
        }

        return ['expected_updated_at' => ['required', 'date_format:Y-m-d\TH:i:s.u\Z'],
            'status' => [$this->isMethod('delete') ? 'prohibited' : 'required', Rule::in(['ACTIVE', 'INACTIVE'])]];
    }
}
