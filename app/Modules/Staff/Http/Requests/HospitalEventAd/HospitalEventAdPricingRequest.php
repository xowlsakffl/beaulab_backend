<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Common\Authorization\AccessPermissions;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventAdPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessPermissions::BEAULAB_AD_PRICING_MANAGE) ?? false;
    }

    public function rules(): array
    {
        return [
            'group' => ['required', Rule::in(HospitalEventAd::placementGroupKeys())],
            'lock_version' => [$this->isMethod('GET') ? 'sometimes' : 'required', 'integer', 'min:0'],
            'current_version_id' => [$this->isMethod('GET') ? 'sometimes' : 'required', 'integer', 'min:0'],
            'prices' => [$this->isMethod('PUT') ? 'required' : 'sometimes', 'array', 'min:1', 'max:3'],
            'prices.*' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'effective_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'pending_id' => [$this->isMethod('DELETE') ? 'required' : 'sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'prices.*.required' => '광고비용을 입력해 주세요.',
            'prices.*.integer' => '광고비용은 정수로 입력해 주세요.',
            'prices.*.min' => '광고비용은 0P 이상으로 입력해 주세요.',
            'prices.*.max' => '광고비용은 2,000,000,000P 이하로 입력해 주세요.',
            'effective_at.date_format' => '예약 시각을 확인해 주세요.',
        ];
    }
}
