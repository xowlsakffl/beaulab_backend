<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Actions\Common\SyncFaqEditorImagesAction;
use App\Domains\Faq\Dto\Staff\FaqForStaffDetailDto;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * FaqCreateForStaffAction 역할 정의.
 * FAQ 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class FaqCreateForStaffAction
{
    public function __construct(
        private readonly FaqCreateForStaffQuery $query,
        private readonly SyncFaqEditorImagesAction $syncFaqEditorImagesAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Faq::class);

        $normalized = $this->normalizePayload($payload);

        $faq = DB::transaction(function () use ($normalized) {
            $created = $this->query->create($normalized);
            $this->syncCategory($created, (int) $normalized['category_id']);

            $syncedContent = $this->syncFaqEditorImagesAction->execute($created, (string) $created->content);
            if ($syncedContent !== (string) $created->content) {
                $created->forceFill(['content' => $syncedContent])->save();
            }

            return $created->fresh([
                'categories:id,name,domain,status,sort_order',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);
        });

        return [
            'faq' => FaqForStaffDetailDto::fromModel($faq)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $payload['question'] = trim((string) $payload['question']);
        $payload['content'] = $this->sanitizeEditorContent((string) $payload['content']);
        $payload['created_by_staff_id'] = $staffId;
        $payload['updated_by_staff_id'] = $staffId;

        return $payload;
    }

    private function sanitizeEditorContent(string $content): string
    {
        $content = trim($content);

        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content) ?? $content;
    }

    private function syncCategory(Faq $faq, int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $faq->categories()->sync([
            $categoryId => ['is_primary' => true],
        ]);
    }
}
