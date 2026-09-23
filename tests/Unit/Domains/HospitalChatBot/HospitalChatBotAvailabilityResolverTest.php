<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
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
