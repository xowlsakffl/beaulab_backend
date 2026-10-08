<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 대시보드 기간. 비우면 최근 30일이다(대시보드 PRD ADM-D01).
 */
final class HospitalChatBotDashboardForStaffRequest extends FormRequest
{
    public const DEFAULT_DAYS = 30;

    public const MAX_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // 형식이 틀린 날짜는 위 규칙이 이미 잡았다. 그 값을 파싱하면 500이 된다.
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $filters = $this->filters();
                if ($filters['from']->diffInDays($filters['to']->startOfDay()) + 1 > self::MAX_DAYS) {
                    $validator->errors()->add('to', '조회 기간은 최대 '.self::MAX_DAYS.'일입니다.');
                }
            },
        ];
    }

    /** @return array{from:CarbonImmutable, to:CarbonImmutable} */
    public function filters(): array
    {
        $timezone = HospitalChatBotAvailabilityResolver::TIMEZONE;
        $today = CarbonImmutable::now($timezone)->startOfDay();

        $to = is_string($this->input('to')) && $this->input('to') !== ''
            ? CarbonImmutable::parse($this->input('to'), $timezone)->startOfDay()
            : $today;
        $from = is_string($this->input('from')) && $this->input('from') !== ''
            ? CarbonImmutable::parse($this->input('from'), $timezone)->startOfDay()
            : $to->subDays(self::DEFAULT_DAYS - 1);

        return ['from' => $from, 'to' => $to->endOfDay()];
    }

    public function attributes(): array
    {
        return ['from' => '시작일', 'to' => '종료일'];
    }
}
