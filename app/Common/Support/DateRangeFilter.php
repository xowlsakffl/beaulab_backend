<?php

declare(strict_types=1);

namespace App\Common\Support;

use Illuminate\Support\Carbon;

final class DateRangeFilter
{
    public static function apply(mixed $builder, string $column, mixed $startDate, mixed $endDate): void
    {
        $start = self::startOfDay($startDate);
        if ($start !== null) {
            $builder->where($column, '>=', $start);
        }

        $end = self::nextDayStart($endDate);
        if ($end !== null) {
            $builder->where($column, '<', $end);
        }
    }

    public static function startOfDay(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    public static function nextDayStart(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->addDay()->startOfDay();
    }
}
