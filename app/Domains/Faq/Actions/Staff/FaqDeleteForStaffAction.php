<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
