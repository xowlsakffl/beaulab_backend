<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * NoticeDeleteForStaffAction 역할 정의.
 * 공지사항 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class NoticeDeleteForStaffAction
{
    public function __construct(
        private readonly NoticeDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    public function execute(Notice $notice): array
    {
        Gate::authorize('delete', $notice);

        DB::transaction(function () use ($notice): void {
            $this->mediaAttachDeleteAction->deleteCollectionMediaBulk($notice, ['attachments', 'editor_images', 'popup_image']);
            $this->query->delete($notice);
        });

        return [
            'deleted' => true,
            'id' => (int) $notice->id,
        ];
    }
}
