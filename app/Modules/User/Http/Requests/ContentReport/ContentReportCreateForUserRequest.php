<?php

namespace App\Modules\User\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContentReportCreateForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(ContentReport::reasons())],
            'reason_text' => ['nullable', 'required_if:reason,'.ContentReport::REASON_OTHER, 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => '신고 사유',
            'reason_text' => '기타 신고 사유',
        ];
    }
}
