<?php

namespace App\Domains\Notice\Actions\Common;

use Illuminate\Support\Facades\Storage;

/**
 * CleanupTempEditorImagesAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
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
