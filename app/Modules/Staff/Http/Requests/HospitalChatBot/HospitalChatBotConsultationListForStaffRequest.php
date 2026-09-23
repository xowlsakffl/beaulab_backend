<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalChatBotConsultationListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        if (is_string($status)) {
            $status = array_values(array_filter(array_map('trim', explode(',', $status))));
        }

        $this->merge(['status' => $status]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in([
                HospitalChatBotConsultationStatus::WAITING,
                HospitalChatBotConsultationStatus::CONNECTED,
                HospitalChatBotConsultationStatus::ENDED,
                HospitalChatBotConsultationStatus::CANCELED,
            ])],
            'assigned_staff_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array{status:list<string>, assigned_staff_id:?int, per_page:int} */
    public function filters(): array
    {
        $validated = $this->validated();

        // 값이 비어 있으면(`?status=`) 필터를 주지 않은 것으로 본다.
        // 빈 배열을 그대로 넘기면 whereIn이 빠져 종료·취소까지 전부 나온다.
        $status = $validated['status'] ?? [];

        return [
            // 필터를 주지 않으면 대기·진행 중인 상담만 보여준다. 기록 조회는 Phase 5에서 다룬다.
            'status' => $status !== [] ? $status : HospitalChatBotConsultationStatus::active(),
            'assigned_staff_id' => isset($validated['assigned_staff_id'])
                ? (int) $validated['assigned_staff_id']
                : null,
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }
}
