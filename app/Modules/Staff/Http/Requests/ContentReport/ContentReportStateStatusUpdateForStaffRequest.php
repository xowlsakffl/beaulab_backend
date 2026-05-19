<?php

namespace App\Modules\Staff\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContentReportStateStatusUpdateForStaffRequest extends FormRequest
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
            'report_status' => ['required', Rule::in(ContentReportState::processableStatuses())],
            'process_reason' => ['nullable', 'string', 'max:500'],
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
            'report_status' => '신고 처리 상태',
            'process_reason' => '처리 사유',
        ];
    }
}
