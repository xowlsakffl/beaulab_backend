<?php

declare(strict_types=1);

namespace App\Domains\HospitalFeature\Definitions;

use RuntimeException;

final class HospitalFeatureDefinitions
{
    /**
     * @return array<int, array{code: string, name: string, sort_order: int}>
     */
    public static function all(): array
    {
        $data = require __DIR__.'/data/features.php';

        if (! is_array($data)) {
            throw new RuntimeException('Hospital feature definition must return an array.');
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }
}
