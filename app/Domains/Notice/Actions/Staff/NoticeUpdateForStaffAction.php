<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Actions\Common\SyncNoticeEditorImagesAction;
use App\Domains\Notice\Dto\Staff\NoticeForStaffDetailDto;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

            if (array_key_exists('attachments', $normalized)) {
                $this->replaceAttachments($saved, $normalized['attachments']);
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

        if (array_key_exists('is_pinned', $payload) && ! (bool) $payload['is_pinned']) {
            $payload['pinned_order'] = 0;
        }

        $payload['updated_by_staff_id'] = $staffId;

        return $payload;
    }

    /**
     * @param array<int, UploadedFile>|null $attachments
     */
    private function replaceAttachments(Notice $notice, ?array $attachments): void
    {
        $this->mediaAttachDeleteAction->deleteCollectionMedia($notice, 'attachments');

        if (! is_array($attachments) || $attachments === []) {
            return;
        }

        $this->mediaAttachDeleteAction->attachMany(
            $notice,
            $attachments,
            'attachments',
            'notice',
            'attachments',
            false,
        );
    }

    private function sanitizeEditorContent(string $content): string
    {
        $content = trim($content);

        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content) ?? $content;
    }
}
