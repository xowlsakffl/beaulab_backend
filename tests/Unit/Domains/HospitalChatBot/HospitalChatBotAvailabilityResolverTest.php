<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Models\HospitalChatBotSetting;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotPublicHolidayCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class HospitalChatBotAvailabilityResolverTest extends TestCase
{
    private HospitalChatBotAvailabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new HospitalChatBotAvailabilityResolver;
    }

    public function test_opening_is_inclusive_and_closing_is_exclusive(): void
    {
        $hours = $this->weekdays('09:00', '18:00');

        $opening = $this->resolver->resolve(
            CarbonImmutable::parse('2026-09-21 09:00:00', HospitalChatBotAvailabilityResolver::TIMEZONE),
            $hours,
            collect(),
        );
        $closing = $this->resolver->resolve(
            CarbonImmutable::parse('2026-09-21 18:00:00', HospitalChatBotAvailabilityResolver::TIMEZONE),
            $hours,
            collect(),
        );

        self::assertTrue($opening['is_available']);
        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_OPEN, $opening['reason']);
        self::assertFalse($closing['is_available']);
        self::assertSame('2026-09-22T09:00:00+09:00', $closing['next_open_at']);
    }

    public function test_closure_date_forces_off_and_next_open_skips_it(): void
    {
        $closures = collect([
            new HospitalChatBotClosureDate([
                'closure_date' => '2026-09-21',
                'reason' => '임시휴무',
            ]),
        ]);

        $result = $this->resolver->resolve(
            CarbonImmutable::parse('2026-09-21 10:00:00', HospitalChatBotAvailabilityResolver::TIMEZONE),
            $this->weekdays('09:00', '18:00'),
            $closures,
        );

        self::assertFalse($result['is_available']);
        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_CLOSED_DATE, $result['reason']);
        self::assertSame('상담원 연결 시간이 아닙니다.', $result['message']);
        self::assertSame('2026-09-22T09:00:00+09:00', $result['next_open_at']);
    }

    public function test_range_closes_both_endpoints_and_reopens_after_end(): void
    {
        $closures = collect([new HospitalChatBotClosureDate(['closure_date' => '2026-10-15', 'end_date' => '2026-10-16'])]);
        foreach (['2026-10-15', '2026-10-16'] as $date) {
            $result = $this->resolver->resolve(CarbonImmutable::parse("$date 10:00", 'Asia/Seoul'), $this->weekdays('09:00', '18:00'), $closures);
            self::assertFalse($result['is_available']);
            self::assertSame('2026-10-19T09:00:00+09:00', $result['next_open_at']);
        }
    }

    public function test_missing_or_invalid_schedule_fails_closed(): void
    {
        $missing = $this->resolver->resolve(
            CarbonImmutable::parse('2026-09-21 10:00:00', HospitalChatBotAvailabilityResolver::TIMEZONE),
            collect(),
            collect(),
        );

        $invalidHours = $this->weekdays('18:00', '09:00');
        $invalid = $this->resolver->resolve(
            CarbonImmutable::parse('2026-09-21 10:00:00', HospitalChatBotAvailabilityResolver::TIMEZONE),
            $invalidHours,
            collect(),
        );

        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_CONFIGURATION_UNAVAILABLE, $missing['reason']);
        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_CONFIGURATION_UNAVAILABLE, $invalid['reason']);
        self::assertFalse($missing['is_available']);
        self::assertNull($missing['next_open_at']);
    }

    public function test_lunch_boundaries_and_same_day_reopening(): void
    {
        $hours = $this->weekdays('09:00', '18:00');
        $setting = new HospitalChatBotSetting(['break_starts_at' => '12:00', 'break_ends_at' => '13:00']);
        foreach (['11:59' => true, '12:00' => false, '12:59' => false, '13:00' => true] as $time => $expected) {
            $result = $this->resolver->resolve(CarbonImmutable::parse("2026-09-21 {$time}", 'Asia/Seoul'), $hours, collect(), $setting);
            self::assertSame($expected, $result['is_available']);
            if (! $expected) {
                self::assertSame('2026-09-21T13:00:00+09:00', $result['next_open_at']);
            }
        }
    }

    public function test_common_lunch_applies_only_during_open_hours_and_fails_closed_if_invalid(): void
    {
        $setting = new HospitalChatBotSetting(['break_starts_at' => '12:00', 'break_ends_at' => '13:00']);
        $hours = $this->weekdays('09:00', '18:00');
        $hours[1]->closes_at = '12:00';
        $hours[2]->opens_at = '12:30';
        $at = fn (string $time) => $this->resolver->resolve(CarbonImmutable::parse($time, 'Asia/Seoul'), $hours, collect(), $setting);
        self::assertTrue($at('2026-09-22 11:59')['is_available']);
        self::assertSame('2026-09-23T13:00:00+09:00', $at('2026-09-22 12:00')['next_open_at']);
        self::assertFalse($at('2026-09-23 12:30')['is_available']);
        self::assertTrue($at('2026-09-23 13:00')['is_available']);
        $setting->break_ends_at = null;
        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_CONFIGURATION_UNAVAILABLE, $at('2026-09-23 13:00')['reason']);
    }

    public function test_selected_holiday_skips_to_next_open_day(): void
    {
        $result = $this->resolver->resolve(CarbonImmutable::parse('2026-10-09 10:00', 'Asia/Seoul'),
            $this->weekdays('09:00', '18:00'), collect(), new HospitalChatBotSetting(['public_holiday_codes' => ['HANGUL_DAY']]));
        self::assertFalse($result['is_available']);
        self::assertSame('2026-10-12T09:00:00+09:00', $result['next_open_at']);
    }

    public function test_korean_lunar_holidays_and_unselected_dates(): void
    {
        $calendar = new HospitalChatBotPublicHolidayCalendar;
        foreach (['2026-02-16' => 'LUNAR_NEW_YEAR_EVE', '2026-02-17' => 'LUNAR_NEW_YEAR',
            '2026-02-18' => 'LUNAR_NEW_YEAR_NEXT_DAY', '2026-05-24' => 'BUDDHAS_BIRTHDAY',
            '2026-09-24' => 'CHUSEOK_EVE', '2026-09-25' => 'CHUSEOK', '2026-09-26' => 'CHUSEOK_NEXT_DAY',
            '2027-02-07' => 'LUNAR_NEW_YEAR'] as $date => $code) {
            self::assertTrue($calendar->isClosed(CarbonImmutable::parse($date, 'Asia/Seoul'), [$code]), $date);
            self::assertFalse($calendar->isClosed(CarbonImmutable::parse($date, 'Asia/Seoul'), []));
        }
        self::assertFalse($calendar->isClosed(CarbonImmutable::parse('2026-02-19', 'Asia/Seoul'), HospitalChatBotPublicHolidayCalendar::CODES));
    }

    public function test_public_schedule_groups_hours_and_clips_holidays_to_fourteen_korean_dates(): void
    {
        $result = $this->resolver->schedule(
            CarbonImmutable::parse('2026-10-01 15:01', 'UTC'),
            $this->weekdays('09:00', '18:00'),
            collect([
                new HospitalChatBotClosureDate(['closure_date' => '2026-10-01', 'end_date' => '2026-10-03', 'reason' => '내부 전용']),
                new HospitalChatBotClosureDate(['closure_date' => '2026-10-15', 'end_date' => '2026-10-16']),
            ]),
            new HospitalChatBotSetting(['break_starts_at' => '12:00', 'break_ends_at' => '13:00',
                'public_holiday_codes' => ['NATIONAL_FOUNDATION_DAY', 'HANGUL_DAY'], 'is_force_closed' => true]),
        );
        self::assertSame('2026-10-02', $result['from']);
        self::assertSame('2026-10-15', $result['to']);
        self::assertSame([
            ['days' => [1, 2, 3, 4, 5], 'opens_at' => '09:00', 'closes_at' => '12:00'],
            ['days' => [1, 2, 3, 4, 5], 'opens_at' => '13:00', 'closes_at' => '18:00'],
        ], $result['periods']);
        self::assertSame(['2026-10-02', '2026-10-03', '2026-10-04', '2026-10-09', '2026-10-10', '2026-10-11', '2026-10-15'], $result['holidays']);
        self::assertStringNotContainsString('내부 전용', json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    public function test_public_schedule_missing_configuration_is_not_an_empty_holiday_schedule(): void
    {
        self::assertNull($this->resolver->schedule(CarbonImmutable::now(), collect(), collect(), null));
    }

    public function test_public_schedule_excludes_lunch_outside_hours_and_includes_lunar_holidays(): void
    {
        $result = $this->resolver->schedule(CarbonImmutable::parse('2026-09-23', 'Asia/Seoul'),
            $this->weekdays('09:00', '12:00'), collect(),
            new HospitalChatBotSetting(['break_starts_at' => '12:00', 'break_ends_at' => '13:00',
                'public_holiday_codes' => ['CHUSEOK_EVE', 'CHUSEOK', 'CHUSEOK_NEXT_DAY']]));
        self::assertCount(1, $result['periods']);
        self::assertContains('2026-09-24', $result['holidays']);
        self::assertContains('2026-09-25', $result['holidays']);
        self::assertContains('2026-09-26', $result['holidays']);
    }

    /** @return Collection<int, HospitalChatBotOperatingHour> */
    private function weekdays(string $opensAt, string $closesAt): Collection
    {
        return collect(range(1, 7))->map(static fn (int $day): HospitalChatBotOperatingHour => new HospitalChatBotOperatingHour([
            'day_of_week' => $day,
            'is_open' => $day <= 5,
            'opens_at' => $day <= 5 ? $opensAt : null,
            'closes_at' => $day <= 5 ? $closesAt : null,
        ]));
    }
}
