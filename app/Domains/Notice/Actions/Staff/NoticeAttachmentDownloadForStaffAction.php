<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NoticeAttachmentDownloadForStaffAction
{
    public function execute(Notice $notice, int $attachmentId): StreamedResponse
    {
        Gate::authorize('view', $notice);

        $notice->loadMissing('attachments');
        $media = $notice->attachments->firstWhere('id', $attachmentId);
        if (! $media || ! Storage::disk((string) $media->disk)->exists((string) $media->path)) {
            throw new CustomException(ErrorCode::NOT_FOUND, '첨부파일을 찾을 수 없습니다.');
        }

        $originalName = $media->metadata['original_name'] ?? null;
        $fileName = is_string($originalName) && trim($originalName) !== ''
            ? trim($originalName)
            : basename((string) $media->path);

        return Storage::disk((string) $media->disk)->download((string) $media->path, $fileName);
    }
}
