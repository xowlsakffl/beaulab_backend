<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * FaqDeleteForStaffAction 역할 정의.
 * FAQ 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class FaqDeleteForStaffAction
{
    public function __construct(
        private readonly FaqDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    public function execute(Faq $faq): array
    {
        Gate::authorize('delete', $faq);

        DB::transaction(function () use ($faq): void {
            $faq->categories()->sync([]);
            $this->mediaAttachDeleteAction->deleteCollectionMediaBulk($faq, ['editor_images']);
            $this->query->delete($faq);
        });

        return [
            'deleted' => true,
            'id' => (int) $faq->id,
        ];
    }
}
