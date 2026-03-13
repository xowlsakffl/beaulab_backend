<?php

namespace App\Domains\Notice\Actions\Common;

use Illuminate\Support\Facades\Storage;

final class CleanupTempEditorImagesAction
{
    public function execute(int $hours): int
    {
        $normalizedHours = max(1, $hours);
        $cutoff = now()->subHours($normalizedHours)->getTimestamp();
        $disk = Storage::disk('public');
        $directory = 'notice/editor-images/temp';

        if (! $disk->exists($directory)) {
            return 0;
        }

        $deleted = 0;

        foreach ($disk->allFiles($directory) as $file) {
            try {
                $lastModified = $disk->lastModified($file);
            } catch (\Throwable) {
                continue;
            }

            if ($lastModified > $cutoff) {
                continue;
            }

            $disk->delete($file);
            $deleted++;
        }

        return $deleted;
    }
}
