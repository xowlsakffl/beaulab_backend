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
            throw new RuntimeException("시드 미디어 파일을 찾을 수 없습니다: {$sourcePath}");
        }

        $content = file_get_contents($sourcePath);

        if ($content === false) {
            throw new RuntimeException("시드 미디어 파일을 읽지 못했습니다: {$sourcePath}");
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
