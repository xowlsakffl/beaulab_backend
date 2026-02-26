<?php

namespace App\Modules\Partner\Http\Requests\VideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class VideoRequestCancelForPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_status' => ['required', 'in:PENDING,PARTNER_CANCELED'],
        ];
    }

    public function attributes(): array
    {
        return [
            'review_status' => '검토 상태',
        ];
    }
}
