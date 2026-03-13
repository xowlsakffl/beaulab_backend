<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

final class FaqGetForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
