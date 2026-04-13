<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * FaqEditorImageCleanupForStaffAction 역할 정의.
 * FAQ 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class FaqEditorImageCleanupForStaffAction
{
    /**
     * @param array<int, string>|null $paths
     * @param array<int, string>|null $urls
     * @return array<string, mixed>
     */
    public function execute(?array $paths = null, ?array $urls = null): array
    {
        Gate::authorize('create', Faq::class);

        $normalizedPaths = $this->normalizeTargetPaths($paths, $urls);
        $deletedPaths = [];

        foreach ($normalizedPaths as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            Storage::disk('public')->delete($path);
            $deletedPaths[] = $path;
        }

        return [
            'requested_count' => count($normalizedPaths),
            'deleted_count' => count($deletedPaths),
            'deleted_paths' => $deletedPaths,
        ];
    }

    /**
     * @param array<int, string>|null $paths
     * @param array<int, string>|null $urls
     * @return array<int, string>
     */
    private function normalizeTargetPaths(?array $paths, ?array $urls): array
    {
        $targets = [];

        foreach ($paths ?? [] as $path) {
            $normalized = $this->normalizeSinglePath($path);
            if ($normalized !== null) {
                $targets[] = $normalized;
            }
        }

        foreach ($urls ?? [] as $url) {
            $parsedPath = parse_url($url, PHP_URL_PATH);
            if (! is_string($parsedPath)) {
                continue;
            }

            $normalized = $this->normalizeSinglePath($parsedPath);
            if ($normalized !== null) {
                $targets[] = $normalized;
            }
        }

        return array_values(array_unique($targets));
    }

    private function normalizeSinglePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 8);
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, 7);
        }

        $path = ltrim($path, '/');

        if (! str_starts_with($path, 'faq/editor-images/temp/')) {
            return null;
        }

        return $path;
    }
}
