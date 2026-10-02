<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Models\HospitalChatBotSetting;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Illuminate\Support\Collection;

final readonly class HospitalChatBotOperatingHoursForStaffDto
{
    /**
     * @param  Collection<int, HospitalChatBotOperatingHour>  $hours
     * @param  Collection<int, HospitalChatBotClosureDate>  $closures
     */
    public static function fromCollections(
        Collection $hours,
        Collection $closures,
        HospitalChatBotAvailabilityResolver $resolver,
        ?HospitalChatBotSetting $setting = null,
    ): array {
        $hoursByDay = $hours->keyBy('day_of_week');

        return [
            'timezone' => HospitalChatBotAvailabilityResolver::TIMEZONE,
            'configured' => $resolver->isConfigured($hours, $setting),
            'break_starts_at' => self::time($setting?->break_starts_at),
            'break_ends_at' => self::time($setting?->break_ends_at),
            'public_holiday_codes' => $setting?->public_holiday_codes ?? [],
            'is_force_closed' => (bool) ($setting?->is_force_closed ?? false),
            'force_closed_reason' => $setting?->force_closed_reason !== null
                ? (string) $setting->force_closed_reason
                : null,
            'weekly_schedule' => collect(range(1, 7))->map(function (int $day) use ($hoursByDay): array {
                /** @var HospitalChatBotOperatingHour|null $hour */
                $hour = $hoursByDay->get($day);

                return [
                    'day_of_week' => $day,
                    'is_open' => (bool) ($hour?->is_open ?? false),
                    'opens_at' => self::time($hour?->opens_at),
                    'closes_at' => self::time($hour?->closes_at),
                ];
            })->all(),
            'closure_dates' => $closures->map(static fn (HospitalChatBotClosureDate $closure): array => [
                'date' => $closure->closure_date->format('Y-m-d'),
                'end_date' => ($closure->end_date ?? $closure->closure_date)->format('Y-m-d'),
                'reason' => $closure->reason !== null ? (string) $closure->reason : null,
            ])->all(),
        ];
    }

    private static function time(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? substr($value, 0, 5) : null;
    }
}
