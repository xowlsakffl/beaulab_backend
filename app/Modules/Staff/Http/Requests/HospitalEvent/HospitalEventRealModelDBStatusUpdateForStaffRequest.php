<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventRealModelDBStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $ids = $this->input('ids');

        if (is_string($ids)) {
            $trimmed = trim($ids);
            $decoded = json_decode($trimmed, true);
            $ids = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
        }

        $this->merge(['ids' => $ids]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', Rule::exists('hospital_event_real_model_dbs', 'id')->whereNull('deleted_at')],
            'status' => ['required', 'string', Rule::in(HospitalEventRealModelDB::statuses())],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => '리얼모델 신청 목록',
            'ids.*' => '리얼모델 신청',
            'status' => '승인여부',
            'reason' => '사유',
        ];
    }
}
