<?php

namespace App\Modules\User\Http\Requests\SiteVisit;

use Illuminate\Foundation\Http\FormRequest;

final class SiteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['session_id' => ['required', 'uuid']];
    }

    public function attributes(): array
    {
        return ['session_id' => '방문 세션'];
    }
}
