<?php

namespace App\Modules\Staff\Http\Requests\RemoteConsultation;

use Illuminate\Foundation\Http\FormRequest;

final class RemoteConsultationListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'gender' => '성별',
            'approval_status' => '승인상태',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'birth_year_from' => '출생연도 시작',
            'birth_year_to' => '출생연도 종료',
            'page' => '페이지',
            'sort' => '정렬 항목',
            'direction' => '정렬 방향',
        ];
    }

    public function rules(): array
    {
        $rules = ['q' => ['nullable', 'string', 'max:100'], 'gender' => ['nullable', 'in:MALE,FEMALE'], 'approval_status' => ['nullable', 'in:PENDING,REVIEWING,APPROVED,REJECTED'], 'start_date' => ['nullable', 'date_format:Y-m-d'], 'end_date' => ['nullable', 'date_format:Y-m-d'], 'birth_year_from' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')], 'birth_year_to' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']];
        if ($this->filled('start_date')) {
            $rules['end_date'][] = 'after_or_equal:start_date';
        }
        if ($this->filled('birth_year_from')) {
            $rules['birth_year_to'][] = 'gte:birth_year_from';
        }

        $rules['sort'] = ['nullable', 'in:id,created_at,answer_count,approval_status'];
        $rules['direction'] = ['nullable', 'in:asc,desc'];

        return $rules;
    }
}
