<?php

namespace App\Modules\Staff\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ContentReportProcessForStaffRequest extends FormRequest
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
            'warning_status' => ['nullable', Rule::in(ContentReportState::warningProcessableStatuses())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $reportStatus = (string) $this->input('report_status');
            $targetType = (string) $this->input('target_type');
            $warningStatus = trim((string) $this->input('warning_status'));

            if ($reportStatus !== ContentReportState::STATUS_ADMIN_HIDDEN) {
                if ($warningStatus !== '') {
                    $validator->errors()->add('warning_status', '정상노출 처리에는 경고여부를 선택할 수 없습니다.');
                }

                return;
            }

            if (trim((string) $this->input('process_reason')) === '') {
                $validator->errors()->add('process_reason', '노출중지 사유를 입력해 주세요.');
            }

            if ($targetType === ContentReportTargetRegistry::ALIAS_HOSPITAL_VIDEO) {
                if ($warningStatus !== '') {
                    $validator->errors()->add('warning_status', '동영상 신고 처리에는 경고여부를 선택할 수 없습니다.');
                }

                return;
            }

            if ($warningStatus === '') {
                $validator->errors()->add('warning_status', '경고여부를 선택해 주세요.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target_type' => '신고 대상 유형',
            'target_id' => '신고 대상 ID',
            'report_status' => '조치유형',
            'process_reason' => '노출중지 사유',
            'warning_status' => '경고여부',
        ];
    }
}
