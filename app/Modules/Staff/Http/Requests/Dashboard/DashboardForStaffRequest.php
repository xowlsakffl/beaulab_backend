<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DashboardForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section' => ['required', 'in:work,metrics,wallet'],
            'include_wallet' => ['sometimes', 'boolean'],
            'start_date' => ['required_if:section,metrics', 'date_format:Y-m-d'],
            'end_date' => ['required_if:section,metrics', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.now('Asia/Seoul')->toDateString()],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->input('section') !== 'metrics') {
                return;
            }
            if (CarbonImmutable::parse($this->input('start_date'))->diffInDays(CarbonImmutable::parse($this->input('end_date'))) > 92) {
                $validator->errors()->add('end_date', '조회 기간은 최대 93일까지 선택할 수 있습니다.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['start_date' => '시작일', 'end_date' => '종료일'];
    }
}
