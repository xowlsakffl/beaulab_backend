<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
