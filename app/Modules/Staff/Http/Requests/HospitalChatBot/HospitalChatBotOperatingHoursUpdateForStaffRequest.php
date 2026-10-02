<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Support\HospitalChatBotPublicHolidayCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class HospitalChatBotOperatingHoursUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekly_schedule' => ['required', 'array', 'size:7'],
            'weekly_schedule.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'weekly_schedule.*.is_open' => ['required', 'boolean'],
            'weekly_schedule.*.opens_at' => ['nullable', 'date_format:H:i'],
            'weekly_schedule.*.closes_at' => ['nullable', 'date_format:H:i'],
            'weekly_schedule.*.break_starts_at' => ['prohibited'],
            'weekly_schedule.*.break_ends_at' => ['prohibited'],
            'break_starts_at' => ['nullable', 'date_format:H:i'],
            'break_ends_at' => ['nullable', 'date_format:H:i'],
            'public_holiday_codes' => ['sometimes', 'array', 'max:17'],
            'public_holiday_codes.*' => ['required', 'string', 'distinct', Rule::in(HospitalChatBotPublicHolidayCalendar::CODES)],
            'closure_dates' => ['present', 'array', 'max:366'],
            'is_force_closed' => ['sometimes', 'boolean'],
            'force_closed_reason' => ['nullable', 'string', 'max:255'],
            'closure_dates.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'closure_dates.*.end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'closure_dates.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }
            $previousEnd = null;
            foreach (collect($this->input('closure_dates', []))->sortBy('date') as $index => $closure) {
                $start = $closure['date'];
                $end = $closure['end_date'] ?? $start;
                if ($end < $start) {
                    $validator->errors()->add("closure_dates.{$index}.end_date", '종료일은 시작일보다 빠를 수 없습니다.');
                }
                if ($previousEnd !== null && $start <= $previousEnd) {
                    $validator->errors()->add("closure_dates.{$index}.date", '지정 휴무 기간이 겹칩니다.');
                }
                $previousEnd = max($previousEnd ?? $end, $end);
            }
            $breakStart = $this->input('break_starts_at');
            $breakEnd = $this->input('break_ends_at');
            if ($breakStart !== null || $breakEnd !== null) {
                if (! is_string($breakStart) || ! is_string($breakEnd) || $breakStart >= $breakEnd) {
                    $validator->errors()->add('break_starts_at', '점심시간의 시작과 종료를 입력하고 종료를 시작보다 늦게 설정해 주세요.');
                }
            }
            foreach ((array) $this->input('weekly_schedule', []) as $index => $item) {
                if (! is_array($item) || ! in_array($item['is_open'] ?? false, [true, 1, '1'], true)) {
                    continue;
                }

                $opensAt = $item['opens_at'] ?? null;
                $closesAt = $item['closes_at'] ?? null;
                if (! is_string($opensAt) || $opensAt === '') {
                    $validator->errors()->add("weekly_schedule.{$index}.opens_at", '상담 시작 시간을 입력해 주세요.');
                }
                if (! is_string($closesAt) || $closesAt === '') {
                    $validator->errors()->add("weekly_schedule.{$index}.closes_at", '상담 종료 시간을 입력해 주세요.');
                }
                if (is_string($opensAt) && is_string($closesAt) && $opensAt >= $closesAt) {
                    $validator->errors()->add(
                        "weekly_schedule.{$index}.closes_at",
                        '상담 종료 시간은 시작 시간보다 늦어야 합니다.',
                    );
                }
            }
        }];
    }

    public function settings(): array
    {
        $validated = $this->validated();

        $isForceClosed = (bool) ($validated['is_force_closed'] ?? false);

        return [
            'is_force_closed' => $isForceClosed,
            'lunch' => $this->exists('break_starts_at') || $this->exists('break_ends_at') ? [
                'break_starts_at' => $validated['break_starts_at'] ?? null,
                'break_ends_at' => $validated['break_ends_at'] ?? null,
            ] : null,
            'public_holiday_codes' => $validated['public_holiday_codes'] ?? null,
            'force_closed_reason' => $isForceClosed && isset($validated['force_closed_reason'])
                && trim((string) $validated['force_closed_reason']) !== ''
                    ? trim((string) $validated['force_closed_reason'])
                    : null,
            'weekly_schedule' => collect($validated['weekly_schedule'])
                ->sortBy('day_of_week')
                ->map(static function (array $item): array {
                    $isOpen = (bool) $item['is_open'];

                    return [
                        'day_of_week' => (int) $item['day_of_week'],
                        'is_open' => $isOpen,
                        'opens_at' => $isOpen ? (string) $item['opens_at'] : null,
                        'closes_at' => $isOpen ? (string) $item['closes_at'] : null,
                    ];
                })
                ->values()
                ->all(),
            'closure_dates' => collect($validated['closure_dates'])
                ->sortBy('date')
                ->map(static fn (array $item): array => [
                    'date' => (string) $item['date'],
                    'end_date' => (string) ($item['end_date'] ?? $item['date']),
                    'reason' => isset($item['reason']) && trim((string) $item['reason']) !== ''
                        ? trim((string) $item['reason'])
                        : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
