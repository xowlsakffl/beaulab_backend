<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Support;

final class EditorImagePath
{
    public static function temporaryPrefix(string $domain): string
    {
        return $domain.'/editor-images/temp/'.(int) auth()->id().'/';
    }

    public static function normalize(string $value): ?string
    {
        $path = parse_url(trim($value), PHP_URL_PATH);
        if (! is_string($path) || preg_match('/[\\\\\x00-\x1F\x7F%]/', $path)) {
            return null;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return null;
            }
        }

        return $path;
    }

    public static function temporary(string $domain, string $value): ?string
    {
        $path = self::normalize($value);

        return $path !== null && str_starts_with($path, self::temporaryPrefix($domain)) ? $path : null;
    }
}
