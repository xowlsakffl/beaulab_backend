<?php

namespace App\Modules\Staff\Http\Requests\Hashtag;

use Illuminate\Foundation\Http\FormRequest;

final class HashtagGetForStaffRequest extends FormRequest
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
