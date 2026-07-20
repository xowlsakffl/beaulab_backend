<?php

namespace App\Modules\User\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoReportForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(ContentReport::hospitalVideoReasons())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => '신고 사유',
        ];
    }
}
