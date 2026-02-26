<?php

namespace App\Modules\Staff\Http\Requests\VideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class VideoRequestUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['sometimes', 'nullable', 'integer', 'exists:hospitals,id'],
            'beauty_id' => ['sometimes', 'nullable', 'integer', 'exists:beauties,id'],
            'doctor_id' => ['sometimes', 'nullable', 'integer', 'exists:doctors,id'],
            'expert_id' => ['sometimes', 'nullable', 'integer', 'exists:experts,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_usage_consented' => ['sometimes', 'boolean'],
            'duration_seconds' => ['sometimes', 'integer', 'min:0'],
            'requested_publish_start_at' => ['sometimes', 'nullable', 'date'],
            'requested_publish_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:requested_publish_start_at'],
            'is_publish_period_unlimited' => ['sometimes', 'boolean'],
            'review_status' => ['sometimes', 'in:PENDING,IN_REVIEW,APPROVED,REJECTED,PARTNER_CANCELED'],
            'reviewed_by_staff_id' => ['sometimes', 'nullable', 'integer', 'exists:account_staffs,id'],
            'reviewed_at' => ['sometimes', 'nullable', 'date'],
            'reject_reason' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reject_reason_detail' => ['sometimes', 'nullable', 'string'],
        ];
    }


    public function attributes(): array
    {
        return [
            'hospital_id' => '병원 ID',
            'beauty_id' => '뷰티 ID',
            'doctor_id' => '의사 ID',
            'expert_id' => '전문가 ID',
            'title' => '제목',
            'description' => '설명',
            'is_usage_consented' => '사용 동의 여부',
            'duration_seconds' => '영상 길이(초)',
            'requested_publish_start_at' => '게시 시작 요청일',
            'requested_publish_end_at' => '게시 종료 요청일',
            'is_publish_period_unlimited' => '게시 기간 무제한 여부',
            'review_status' => '검토 상태',
            'reviewed_by_staff_id' => '검토 담당자 ID',
            'reviewed_at' => '검토 일시',
            'reject_reason' => '반려 사유',
            'reject_reason_detail' => '반려 사유 상세',
        ];
    }

}
