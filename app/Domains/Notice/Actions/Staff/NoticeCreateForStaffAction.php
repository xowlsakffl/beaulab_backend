<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Actions\Common\SyncNoticeEditorImagesAction;
use App\Domains\Notice\Dto\Staff\NoticeForStaffDetailDto;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class NoticeCreateForStaffAction
{
    public function __construct(
        private readonly NoticeCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
        private readonly SyncNoticeEditorImagesAction $syncNoticeEditorImagesAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Notice::class);

        $normalized = $this->normalizePayload($payload);

        $notice = DB::transaction(function () use ($normalized) {
            $created = $this->query->create($normalized);

            $this->attachFiles($created, $normalized['attachments'] ?? []);

            $syncedContent = $this->syncNoticeEditorImagesAction->execute($created, (string) $created->content);
            if ($syncedContent !== (string) $created->content) {
                $created->forceFill(['content' => $syncedContent])->save();
            }

            return $created->fresh([
                'attachments',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);
        });

        return [
            'notice' => NoticeForStaffDetailDto::fromModel($notice)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $payload['title'] = trim((string) $payload['title']);
        $payload['content'] = $this->sanitizeEditorContent((string) $payload['content']);

        if ((bool) ($payload['is_publish_period_unlimited'] ?? true)) {
            $payload['publish_end_at'] = null;
        }

        $payload['created_by_staff_id'] = $staffId;
        $payload['updated_by_staff_id'] = $staffId;

        return $payload;
    }

    /**
     * @param array<int, UploadedFile> $attachments
     */
    private function attachFiles(Notice $notice, array $attachments): void
    {
        if ($attachments === []) {
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
