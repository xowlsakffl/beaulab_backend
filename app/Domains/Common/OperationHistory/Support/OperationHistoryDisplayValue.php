<?php

namespace App\Domains\Common\OperationHistory\Support;

final class OperationHistoryDisplayValue
{
    public static function fileName(?string $path): string
    {
        return $path === null || trim($path) === '' ? '-' : basename($path);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    public static function lines(array $items): string
    {
        $lines = array_values(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), $items),
            static fn (string $item): bool => $item !== '',
        ));

        return $lines === [] ? '-' : implode("\n", $lines);
    }
}
