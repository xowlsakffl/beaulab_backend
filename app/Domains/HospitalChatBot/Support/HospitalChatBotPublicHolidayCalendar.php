<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use Carbon\CarbonImmutable;
use IntlCalendar;
use RuntimeException;

final class HospitalChatBotPublicHolidayCalendar
{
    public const CODES = [
        'NEW_YEAR', 'LUNAR_NEW_YEAR_EVE', 'LUNAR_NEW_YEAR', 'LUNAR_NEW_YEAR_NEXT_DAY',
        'INDEPENDENCE_MOVEMENT_DAY', 'LABOR_DAY', 'CHILDRENS_DAY', 'BUDDHAS_BIRTHDAY',
        'MEMORIAL_DAY', 'CONSTITUTION_DAY', 'LIBERATION_DAY', 'CHUSEOK_EVE', 'CHUSEOK',
        'CHUSEOK_NEXT_DAY', 'NATIONAL_FOUNDATION_DAY', 'HANGUL_DAY', 'CHRISTMAS',
    ];

    private const SOLAR = [
        '01-01' => 'NEW_YEAR', '03-01' => 'INDEPENDENCE_MOVEMENT_DAY', '05-01' => 'LABOR_DAY',
        '05-05' => 'CHILDRENS_DAY', '06-06' => 'MEMORIAL_DAY', '07-17' => 'CONSTITUTION_DAY',
        '08-15' => 'LIBERATION_DAY', '10-03' => 'NATIONAL_FOUNDATION_DAY', '10-09' => 'HANGUL_DAY', '12-25' => 'CHRISTMAS',
    ];

    /** 선택한 기념일 당일만 판정한다. 임시·대체 휴무는 지정 휴무로 등록한다. */
    public function isClosed(CarbonImmutable $date, array $codes): bool
    {
        if ($codes === []) {
            return false;
        }
        if (in_array(self::SOLAR[$date->format('m-d')] ?? '', $codes, true)) {
            return true;
        }
        $lunarCodes = array_diff($codes, array_values(self::SOLAR));
        if ($lunarCodes === []) {
            return false;
        }

        $calendar = IntlCalendar::createInstance('Asia/Seoul', 'ko_KR@calendar=dangi');
        if ($calendar === null || $calendar->getType() !== 'dangi') {
            throw new RuntimeException('한국 음력 달력을 초기화하지 못했습니다. PHP intl 설정을 확인해 주세요.');
        }
        $matches = static function (CarbonImmutable $day, int $month, int $dayOfMonth) use ($calendar): bool {
            $calendar->setTime($day->setTime(12, 0)->getTimestamp() * 1000.0);

            return $calendar->get(IntlCalendar::FIELD_IS_LEAP_MONTH) === 0
                && $calendar->get(IntlCalendar::FIELD_MONTH) + 1 === $month
                && $calendar->get(IntlCalendar::FIELD_DAY_OF_MONTH) === $dayOfMonth;
        };
        foreach ($lunarCodes as $code) {
            $closed = match ($code) {
                'LUNAR_NEW_YEAR_EVE' => $matches($date->addDay(), 1, 1),
                'LUNAR_NEW_YEAR' => $matches($date, 1, 1),
                'LUNAR_NEW_YEAR_NEXT_DAY' => $matches($date->subDay(), 1, 1),
                'BUDDHAS_BIRTHDAY' => $matches($date, 4, 8),
                'CHUSEOK_EVE' => $matches($date, 8, 14),
                'CHUSEOK' => $matches($date, 8, 15),
                'CHUSEOK_NEXT_DAY' => $matches($date, 8, 16),
                default => false,
            };
            if ($closed) {
                return true;
            }
        }

        return false;
    }
}
