<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Actions\Common\SyncNoticeEditorImagesAction;
use App\Domains\Notice\Dto\Staff\NoticeForStaffDetailDto;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeUpdateForStaffQuery;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * NoticeUpdateForStaffAction 역할 정의.
 * 공지사항 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class NoticeUpdateForStaffAction
{
    public function __construct(
        private readonly NoticeUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
        private readonly SyncNoticeEditorImagesAction $syncNoticeEditorImagesAction,
    ) {}

    public function execute(Notice $notice, array $payload): array
    {
        Gate::authorize('update', $notice);

        $normalized = $this->normalizePayload($payload);

        $updated = DB::transaction(function () use ($notice, $normalized) {
            $saved = $this->query->update($notice, $normalized);

            if (array_key_exists('existing_attachment_ids', $normalized) || array_key_exists('attachments', $normalized)) {
                $this->syncAttachments(
                    $saved,
                    $normalized['existing_attachment_ids'] ?? [],
                    $this->onlyFiles($normalized['attachments'] ?? []),
                );
            }

            $syncedContent = $this->syncNoticeEditorImagesAction->execute($saved, (string) $saved->content);
            if ($syncedContent !== (string) $saved->content) {
                $saved->forceFill(['content' => $syncedContent])->save();
            }

            return $saved->fresh([
                'attachments',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);
        });

        return [
            'notice' => NoticeForStaffDetailDto::fromModel($updated)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        if (array_key_exists('title', $payload)) {
            $payload['title'] = trim((string) $payload['title']);
        }

        if (array_key_exists('content', $payload)) {
            $payload['content'] = $this->sanitizeEditorContent((string) $payload['content']);
        }

        if (
            array_key_exists('is_publish_period_unlimited', $payload)
            && (bool) $payload['is_publish_period_unlimited']
        ) {
            $payload['publish_end_at'] = null;
        }

        $payload['updated_by_staff_id'] = $staffId;

        return $payload;
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, UploadedFile>
     */
    private function onlyFiles(array $files): array
    {
        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }

    /**
     * @param array<int, int|string> $existingAttachmentIds
     * @param array<int, UploadedFile> $newAttachments
     */
    private function syncAttachments(Notice $notice, array $existingAttachmentIds, array $newAttachments): void
    {
        $currentMedia = Media::query()
            ->for($notice)
            ->collection('attachments')
            ->ordered()
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->id);

        $keptMediaIds = collect($existingAttachmentIds)
            ->map(static fn (int|string $mediaId): int => (int) $mediaId)
            ->filter(static fn (int $mediaId): bool => $mediaId > 0 && $currentMedia->has($mediaId))
            ->unique()
            ->values();

        $deletedMediaIds = $currentMedia->keys()->diff($keptMediaIds);

        if ($deletedMediaIds->isNotEmpty()) {
            Media::query()
                ->whereIn('id', $deletedMediaIds->all())
                ->get()
                ->each(function (Media $media): void {
                    Storage::disk($media->disk)->delete($media->path);
                    $media->delete();
                });
        }

        $keptMediaIds->each(function (int $mediaId, int $index) use ($currentMedia): void {
            $media = $currentMedia->get($mediaId);

            if (! $media) {
                return;
            }

            $media->setSortOrder($index);
        });

        $baseSortOrder = $keptMediaIds->count();
        foreach (array_values($newAttachments) as $index => $file) {
            $this->mediaAttachDeleteAction->attachOne(
                $notice,
                $file,
                'attachments',
                'notice',
                'attachments',
                false,
                $baseSortOrder + $index,
            );
        }
    }

    private function sanitizeEditorContent(string $content): string
    {
        $content = trim($content);

        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content) ?? $content;
    }
}
