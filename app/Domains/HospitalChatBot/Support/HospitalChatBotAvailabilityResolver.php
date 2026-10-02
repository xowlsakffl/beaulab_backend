<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Models\HospitalChatBotSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class HospitalChatBotAvailabilityResolver
{
    public function __construct(private readonly HospitalChatBotPublicHolidayCalendar $holidays = new HospitalChatBotPublicHolidayCalendar) {}

    public const TIMEZONE = 'Asia/Seoul';

    public const REASON_OPEN = 'OPEN';

    public const REASON_OUTSIDE_HOURS = 'OUTSIDE_HOURS';

    public const REASON_CLOSED_DATE = 'CLOSED_DATE';

    public const REASON_CONFIGURATION_UNAVAILABLE = 'CONFIGURATION_UNAVAILABLE';

    public const REASON_FORCE_CLOSED = 'FORCE_CLOSED';

    private const CLOSED_MESSAGE = '상담원 연결 시간이 아닙니다.';

    /**
     * @param  Collection<int, HospitalChatBotOperatingHour>  $hours
     * @param  Collection<int, HospitalChatBotClosureDate>  $closures
     * @return array{timezone:string,checked_at:string,is_available:bool,reason:string,message:?string,next_open_at:?string}
     */
    public function resolve(
        CarbonInterface $now,
        Collection $hours,
        Collection $closures,
        ?HospitalChatBotSetting $setting = null,
    ): array {
        $checkedAt = CarbonImmutable::instance($now)->setTimezone(self::TIMEZONE);

        // 강제 중단은 요일·휴무일 설정보다 우선한다. 다시 켜는 시점을 알 수 없으므로 next_open_at은 비운다.
        if ($setting?->is_force_closed === true) {
            return $this->result($checkedAt, false, self::REASON_FORCE_CLOSED, null);
        }

        if (! $this->isConfigured($hours, $setting)) {
            return $this->result($checkedAt, false, self::REASON_CONFIGURATION_UNAVAILABLE, null);
        }

        $closureDates = $closures;

        $hoursByDay = $hours->keyBy('day_of_week');
        $holidayCodes = $setting?->public_holiday_codes ?? [];
        if ($this->closureAt($checkedAt, $closureDates) !== null || $this->holidays->isClosed($checkedAt, $holidayCodes)) {
            return $this->result(
                $checkedAt,
                false,
                self::REASON_CLOSED_DATE,
                $this->nextOpenAt($checkedAt, $hoursByDay, $closureDates, $holidayCodes, $setting),
            );
        }

        /** @var HospitalChatBotOperatingHour $today */
        $today = $hoursByDay->get($checkedAt->isoWeekday());
        if ($today->is_open && $this->isWithin($checkedAt, $today, $setting)) {
            return $this->result($checkedAt, true, self::REASON_OPEN, null);
        }

        return $this->result(
            $checkedAt,
            false,
            self::REASON_OUTSIDE_HOURS,
            $this->nextOpenAt($checkedAt, $hoursByDay, $closureDates, $holidayCodes, $setting),
        );
    }

    /** 병의원에 공개할 운영시간과 오늘을 포함한 14일의 휴무일만 반환한다. */
    public function schedule(CarbonInterface $now, Collection $hours, Collection $closures, ?HospitalChatBotSetting $setting): ?array
    {
        if (! $this->isConfigured($hours, $setting)) {
            return null;
        }

        $start = CarbonImmutable::instance($now)->setTimezone(self::TIMEZONE)->startOfDay();
        $periods = [];
        $openDays = [];
        $breakStart = $this->time($setting?->break_starts_at);
        $breakEnd = $this->time($setting?->break_ends_at);
        foreach ($hours->sortBy('day_of_week') as $hour) {
            if (! $hour->is_open) {
                continue;
            }
            $open = $this->time($hour->opens_at);
            $close = $this->time($hour->closes_at);
            $segments = [[$open, $close]];
            if ($breakStart !== null && $breakEnd !== null && $breakStart < $close && $breakEnd > $open) {
                $segments = [[$open, max($open, $breakStart)], [min($close, $breakEnd), $close]];
            }
            foreach ($segments as [$from, $to]) {
                if ($from >= $to) {
                    continue;
                }
                $key = $from.'-'.$to;
                $periods[$key] ??= ['days' => [], 'opens_at' => $from, 'closes_at' => $to];
                $periods[$key]['days'][] = $hour->day_of_week;
                $openDays[$hour->day_of_week] = true;
            }
        }
        $holidays = [];
        for ($offset = 0; $offset < 14; $offset++) {
            $day = $start->addDays($offset);
            if (! isset($openDays[$day->isoWeekday()])
                || $this->closureAt($day, $closures) !== null
                || $this->holidays->isClosed($day, $setting?->public_holiday_codes ?? [])) {
                $holidays[] = $day->toDateString();
            }
        }

        return [
            'periods' => array_values($periods),
            'break_starts_at' => $breakStart,
            'break_ends_at' => $breakEnd,
            'holidays' => $holidays,
            'from' => $start->toDateString(),
            'to' => $start->addDays(13)->toDateString(),
        ];
    }

    /** @param Collection<int, HospitalChatBotOperatingHour> $hours */
    public function isConfigured(Collection $hours, ?HospitalChatBotSetting $setting = null): bool
    {
        if ($hours->count() !== 7 || $hours->pluck('day_of_week')->unique()->sort()->values()->all() !== range(1, 7)) {
            return false;
        }

        if ($setting?->break_starts_at !== null || $setting?->break_ends_at !== null) {
            $start = $this->time($setting?->break_starts_at);
            $end = $this->time($setting?->break_ends_at);
            if ($start === null || $end === null || $start >= $end) {
                return false;
            }
        }

        return $hours->every(function (HospitalChatBotOperatingHour $hour): bool {
            if (! $hour->is_open) {
                return true;
            }

            $opensAt = $this->time($hour->opens_at);
            $closesAt = $this->time($hour->closes_at);

            return $opensAt !== null && $closesAt !== null && $opensAt < $closesAt;
        });
    }

    private function isWithin(CarbonImmutable $now, HospitalChatBotOperatingHour $hour, ?HospitalChatBotSetting $setting): bool
    {
        $date = $now->format('Y-m-d');
        $opensAt = CarbonImmutable::parse("{$date} {$this->time($hour->opens_at)}", self::TIMEZONE);
        $closesAt = CarbonImmutable::parse("{$date} {$this->time($hour->closes_at)}", self::TIMEZONE);

        $time = $now->format('H:i');
        $onBreak = $setting?->break_starts_at !== null && $setting?->break_ends_at !== null
            && $time >= $this->time($setting->break_starts_at) && $time < $this->time($setting->break_ends_at);

        return ! $onBreak && $now->greaterThanOrEqualTo($opensAt) && $now->lessThan($closesAt);
    }

    /**
     * @param  Collection<int, HospitalChatBotOperatingHour>  $hoursByDay
     * @param  Collection<int, HospitalChatBotClosureDate>  $closureDates
     */
    private function nextOpenAt(
        CarbonImmutable $now,
        Collection $hoursByDay,
        Collection $closureDates,
        array $holidayCodes,
        ?HospitalChatBotSetting $setting,
    ): ?CarbonImmutable {
        if ($hoursByDay->every(static fn (HospitalChatBotOperatingHour $hour): bool => ! $hour->is_open)) {
            return null;
        }

        $date = $now->startOfDay();
        for ($offset = 0; $offset <= 370 + $closureDates->count(); $offset++, $date = $date->addDay()) {
            $closure = $this->closureAt($date, $closureDates);
            if ($closure !== null) {
                $date = ($closure->end_date ?? $closure->closure_date)->setTimezone(self::TIMEZONE)->startOfDay();

                continue;
            }
            if ($this->holidays->isClosed($date, $holidayCodes)) {
                continue;
            }

            /** @var HospitalChatBotOperatingHour $hour */
            $hour = $hoursByDay->get($date->isoWeekday());
            if (! $hour->is_open) {
                continue;
            }

            $candidates = array_filter([$this->time($hour->opens_at), $this->time($setting?->break_ends_at)]);
            sort($candidates);
            foreach ($candidates as $time) {
                $candidate = CarbonImmutable::parse($date->format('Y-m-d').' '.$time, self::TIMEZONE);
                if ($candidate->greaterThan($now) && $this->isWithin($candidate, $hour, $setting)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function closureAt(CarbonImmutable $date, Collection $closures): ?HospitalChatBotClosureDate
    {
        $day = $date->format('Y-m-d');

        return $closures->first(static fn (HospitalChatBotClosureDate $closure): bool => $closure->closure_date->format('Y-m-d') <= $day
            && ($closure->end_date ?? $closure->closure_date)->format('Y-m-d') >= $day);
    }

    private function time(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) {
            return null;
        }

        return substr($value, 0, 5);
    }

    /**
     * @return array{timezone:string,checked_at:string,is_available:bool,reason:string,message:?string,next_open_at:?string}
     */
    private function result(
        CarbonImmutable $checkedAt,
        bool $isAvailable,
        string $reason,
        ?CarbonImmutable $nextOpenAt,
    ): array {
        return [
            'timezone' => self::TIMEZONE,
            'checked_at' => $checkedAt->toIso8601String(),
            'is_available' => $isAvailable,
            'reason' => $reason,
            'message' => $isAvailable ? null : self::CLOSED_MESSAGE,
            'next_open_at' => $nextOpenAt?->toIso8601String(),
        ];
    }
}
