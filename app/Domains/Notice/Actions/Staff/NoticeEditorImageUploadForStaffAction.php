<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Models\Notice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * NoticeEditorImageUploadForStaffAction 역할 정의.
 * 공지사항 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class NoticeEditorImageUploadForStaffAction
{
    public function __construct(
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(UploadedFile $image, ?int $noticeId = null): array
    {
        if ($noticeId !== null) {
            $notice = Notice::query()->findOrFail($noticeId);
            Gate::authorize('update', $notice);

            $media = $this->mediaAttachDeleteAction->attachOne(
                $notice,
                $image,
                'editor_images',
                'notice',
                'editor-images',
                false,
            );

            return [
                'notice_id' => (int) $notice->id,
                'media_id' => $media?->id,
                'disk' => $media?->disk ?? 'public',
                'path' => $media?->path,
                'url' => $media ? Storage::disk((string) $media->disk)->url((string) $media->path) : null,
                'is_temporary' => false,
            ];
        }

        Gate::authorize('create', Notice::class);

        $disk = 'public';
        $path = Storage::disk($disk)->putFile('notice/editor-images/temp', $image);

        return [
            'notice_id' => null,
            'media_id' => null,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'is_temporary' => true,
        ];
    }
}
