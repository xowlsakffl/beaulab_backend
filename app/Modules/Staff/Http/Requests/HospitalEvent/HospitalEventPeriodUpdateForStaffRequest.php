<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventPeriodUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('event_end_at', $data) && $data['event_end_at'] === '') {
            $data['event_end_at'] = null;
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_event_period_unlimited' => ['required', 'boolean'],
            'event_start_at' => ['required', 'date'],
            'event_end_at' => [
                Rule::requiredIf(fn (): bool => ! $this->boolean('is_event_period_unlimited')),
                'nullable',
                'date',
                'after_or_equal:event_start_at',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'is_event_period_unlimited' => '이벤트 기간 무제한 여부',
            'event_start_at' => '이벤트 시작일',
            'event_end_at' => '이벤트 종료일',
        ];
    }
}
