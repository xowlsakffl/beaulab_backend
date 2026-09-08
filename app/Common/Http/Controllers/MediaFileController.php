<?php

declare(strict_types=1);

namespace App\Common\Http\Controllers;

use App\Domains\Common\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaFileController
{
    public function __invoke(Media $media, string $variant = 'original'): StreamedResponse
    {
        abort_unless($media->isPrivate(), 404);
        abort_unless(in_array($variant, ['original', 'thumb', 'medium'], true), 404);
        $path = $variant === 'original' ? $media->path : ($media->metadata['variants'][$variant]['path'] ?? null);
        abort_unless(is_string($path) && Storage::disk($media->disk)->exists($path), 404);

        $inline = in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);

        return Storage::disk($media->disk)->response($path, basename($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Referrer-Policy' => 'no-referrer',
        ], $inline ? 'inline' : 'attachment');
    }
}
