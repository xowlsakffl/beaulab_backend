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

        if (! $this->isConfigured($hours)) {
            return $this->result($checkedAt, false, self::REASON_CONFIGURATION_UNAVAILABLE, null);
        }

        $closureDates = $closures
            ->mapWithKeys(static fn (HospitalChatBotClosureDate $closure): array => [
                $closure->closure_date->format('Y-m-d') => true,
            ]);

        $hoursByDay = $hours->keyBy('day_of_week');
        if ($closureDates->has($checkedAt->format('Y-m-d'))) {
            return $this->result(
                $checkedAt,
                false,
                self::REASON_CLOSED_DATE,
                $this->nextOpenAt($checkedAt, $hoursByDay, $closureDates),
            );
        }

        /** @var HospitalChatBotOperatingHour $today */
        $today = $hoursByDay->get($checkedAt->isoWeekday());
        if ($today->is_open && $this->isWithin($checkedAt, $today)) {
            return $this->result($checkedAt, true, self::REASON_OPEN, null);
        }

        return $this->result(
            $checkedAt,
            false,
            self::REASON_OUTSIDE_HOURS,
            $this->nextOpenAt($checkedAt, $hoursByDay, $closureDates),
        );
    }

    /** @param Collection<int, HospitalChatBotOperatingHour> $hours */
    public function isConfigured(Collection $hours): bool
    {
        if ($hours->count() !== 7 || $hours->pluck('day_of_week')->unique()->sort()->values()->all() !== range(1, 7)) {
            return false;
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

    private function isWithin(CarbonImmutable $now, HospitalChatBotOperatingHour $hour): bool
    {
        $date = $now->format('Y-m-d');
        $opensAt = CarbonImmutable::parse("{$date} {$this->time($hour->opens_at)}", self::TIMEZONE);
        $closesAt = CarbonImmutable::parse("{$date} {$this->time($hour->closes_at)}", self::TIMEZONE);

        return $now->greaterThanOrEqualTo($opensAt) && $now->lessThan($closesAt);
    }

    /**
     * @param  Collection<int, HospitalChatBotOperatingHour>  $hoursByDay
     * @param  Collection<string, bool>  $closureDates
     */
    private function nextOpenAt(
        CarbonImmutable $now,
        Collection $hoursByDay,
        Collection $closureDates,
    ): ?CarbonImmutable {
        if ($hoursByDay->every(static fn (HospitalChatBotOperatingHour $hour): bool => ! $hour->is_open)) {
            return null;
        }

        for ($offset = 0; $offset <= 370; $offset++) {
            $date = $now->startOfDay()->addDays($offset);
            if ($closureDates->has($date->format('Y-m-d'))) {
                continue;
            }

            /** @var HospitalChatBotOperatingHour $hour */
            $hour = $hoursByDay->get($date->isoWeekday());
            if (! $hour->is_open) {
                continue;
            }

            $candidate = CarbonImmutable::parse(
                $date->format('Y-m-d').' '.$this->time($hour->opens_at),
                self::TIMEZONE,
            );

            if ($candidate->greaterThan($now)) {
                return $candidate;
            }
        }

        return null;
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
