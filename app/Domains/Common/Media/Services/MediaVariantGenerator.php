<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Services;

use Illuminate\Support\Facades\Storage;

final class MediaVariantGenerator
{
    private const VARIANTS = [
        'thumb' => ['max_width' => 320, 'max_height' => 320],
        'medium' => ['max_width' => 960, 'max_height' => 960],
    ];

    public function __construct(
        private readonly MediaStorage $mediaStorage,
    ) {}

    /**
     * @return array<string, array{path:string,mime_type:string,size:int,width:int,height:int}>
     */
    public function generate(string $disk, string $path, ?string $mimeType): array
    {
        if (! $this->canGenerate($mimeType)) {
            return [];
        }

        try {
            $contents = Storage::disk($disk)->get($path);
            $image = @imagecreatefromstring($contents);
            $size = @getimagesizefromstring($contents);

            if (! $image || ! is_array($size)) {
                return [];
            }

            $width = (int) $size[0];
            $height = (int) $size[1];
            $variants = [];

            foreach (self::VARIANTS as $name => $config) {
                $variant = $this->createVariant(
                    image: $image,
                    sourcePath: $path,
                    mimeType: (string) $mimeType,
                    sourceWidth: $width,
                    sourceHeight: $height,
                    maxWidth: $config['max_width'],
                    maxHeight: $config['max_height'],
                    name: $name,
                );

                if ($variant === null) {
                    continue;
                }

                if ($this->shouldUseOriginal(
                    sourceSize: strlen($contents),
                    variantSize: strlen($variant['contents']),
                    sourceWidth: $width,
                    sourceHeight: $height,
                    maxWidth: $config['max_width'],
                    maxHeight: $config['max_height'],
                )) {
                    continue;
                }

                $stored = Storage::disk($disk)->put(
                    $variant['path'],
                    $variant['contents'],
                    $this->mediaStorage->writeOptions($disk),
                );

                if (! $stored) {
                    continue;
                }

                $variants[$name] = [
                    'path' => $variant['path'],
                    'mime_type' => (string) $mimeType,
                    'size' => strlen($variant['contents']),
                    'width' => $variant['width'],
                    'height' => $variant['height'],
                ];
            }

            imagedestroy($image);

            return $variants;
        } catch (\Throwable) {
            return [];
        }
    }

    private function shouldUseOriginal(
        int $sourceSize,
        int $variantSize,
        int $sourceWidth,
        int $sourceHeight,
        int $maxWidth,
        int $maxHeight,
    ): bool {
        if ($variantSize < $sourceSize) {
            return false;
        }

        return $sourceWidth <= $maxWidth * 2 && $sourceHeight <= $maxHeight * 2;
    }

    private function canGenerate(?string $mimeType): bool
    {
        if (! extension_loaded('gd') || ! is_string($mimeType) || ! function_exists('imagecreatefromstring')) {
            return false;
        }

        return match ($mimeType) {
            'image/jpeg' => function_exists('imagejpeg'),
            'image/png' => function_exists('imagepng'),
            'image/webp' => function_exists('imagewebp'),
            default => false,
        };
    }

    /**
     * @return array{path:string,contents:string,width:int,height:int}|null
     */
    private function createVariant(
        \GdImage $image,
        string $sourcePath,
        string $mimeType,
        int $sourceWidth,
        int $sourceHeight,
        int $maxWidth,
        int $maxHeight,
        string $name,
    ): ?array {
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return null;
        }

        if ($sourceWidth <= $maxWidth && $sourceHeight <= $maxHeight) {
            return null;
        }

        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        match ($mimeType) {
            'image/png' => imagepng($canvas, null, 6),
            'image/webp' => imagewebp($canvas, null, 82),
            default => imagejpeg($canvas, null, 82),
        };
        $contents = (string) ob_get_clean();
        imagedestroy($canvas);

        if ($contents === '') {
            return null;
        }

        return [
            'path' => $this->variantPath($sourcePath, $name),
            'contents' => $contents,
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    private function variantPath(string $sourcePath, string $name): string
    {
        $directory = trim(pathinfo($sourcePath, PATHINFO_DIRNAME), '.');
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $variantFilename = "{$filename}-{$name}".($extension !== '' ? ".{$extension}" : '');

        return $directory === '' ? $variantFilename : "{$directory}/{$variantFilename}";
    }
}
