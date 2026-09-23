<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;
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
            'closure_dates' => ['required', 'array', 'max:366'],
            'closure_dates.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'closure_dates.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
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

        return [
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
                    'reason' => isset($item['reason']) && trim((string) $item['reason']) !== ''
                        ? trim((string) $item['reason'])
                        : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
