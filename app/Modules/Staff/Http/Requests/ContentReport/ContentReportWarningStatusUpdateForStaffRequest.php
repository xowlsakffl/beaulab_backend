<?php

namespace App\Modules\Staff\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContentReportWarningStatusUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(ContentReportTargetRegistry::aliases())],
            'target_id' => ['required', 'integer', 'min:1'],
            'warning_status' => ['required', Rule::in(ContentReportState::warningProcessableStatuses())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target_type' => '신고 대상 유형',
            'target_id' => '신고 대상 ID',
            'warning_status' => '경고 처리 상태',
        ];
    }
}
