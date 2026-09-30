<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationFilters;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 내보내기는 목록과 같은 조건을 받는다(PRD ADM-S06).
 *
 * 규칙을 필터 클래스에서 가져와 한 곳만 고치면 되게 한다. 규칙을 따로 적어 두면
 * 목록에 필터가 하나 늘어날 때 내보내기만 조용히 뒤처진다.
 */
final class HospitalChatBotConsultationExportForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['status' => HospitalChatBotConsultationFilters::splitStatus($this->input('status'))]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(HospitalChatBotConsultationFilters::rules(), [
            // 기간은 필수다. 전체 기간을 한 번에 내보내면 동기 처리로 감당되지 않는다.
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return HospitalChatBotConsultationFilters::fromValidated($this->validated());
    }
}
