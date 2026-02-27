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
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
