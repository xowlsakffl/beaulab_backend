<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use Illuminate\Foundation\Http\FormRequest;

final class NoticeGetForStaffRequest extends FormRequest
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
