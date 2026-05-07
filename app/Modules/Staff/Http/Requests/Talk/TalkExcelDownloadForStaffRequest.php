<?php

namespace App\Modules\Staff\Http\Requests\Talk;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;
use Throwable;

final class TalkExcelDownloadForStaffRequest extends TalkListForStaffRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                $startDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('start_date'))->startOfDay();
                $endDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('end_date'))->startOfDay();
            } catch (Throwable) {
                return;
            }

            if ($endDate->greaterThan($startDate->addMonthNoOverflow())) {
                $validator->errors()->add('end_date', '엑셀 다운로드 기간은 최대 1개월까지만 가능합니다.');
            }
        });
    }
}
