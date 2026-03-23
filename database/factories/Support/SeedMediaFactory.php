<?php

namespace Database\Factories\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

final class SeedMediaFactory
{
    public static function image(string $prefix = 'seed'): UploadedFile
    {
        $sourcePath = public_path('test.png');

        if (! is_file($sourcePath)) {
            throw new RuntimeException("Seed media file not found: {$sourcePath}");
        }

        $content = file_get_contents($sourcePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read seed media file: {$sourcePath}");
        }

        $fileName = sprintf('%s-%s.png', $prefix, str_replace('.', '', uniqid('', true)));

        return UploadedFile::fake()->createWithContent($fileName, $content);
    }

    /**
     * @return array<int, UploadedFile>
     */
    public static function images(string $prefix, int $count): array
    {
        $files = [];

        for ($index = 0; $index < $count; $index++) {
            $files[] = self::image(sprintf('%s-%02d', $prefix, $index + 1));
        }

        return $files;
    }
}
