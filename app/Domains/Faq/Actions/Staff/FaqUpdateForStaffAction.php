<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Actions\Common\SyncFaqEditorImagesAction;
use App\Domains\Faq\Dto\Staff\FaqForStaffDetailDto;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class FaqUpdateForStaffAction
{
    public function __construct(
        private readonly FaqUpdateForStaffQuery $query,
        private readonly SyncFaqEditorImagesAction $syncFaqEditorImagesAction,
    ) {}

    public function execute(Faq $faq, array $payload): array
    {
        Gate::authorize('update', $faq);

        $normalized = $this->normalizePayload($payload);

        $updated = DB::transaction(function () use ($faq, $normalized) {
            $saved = $this->query->update($faq, $normalized);

            if (array_key_exists('category_id', $normalized)) {
                $this->syncCategory($saved, (int) $normalized['category_id']);
            }

            $syncedContent = $this->syncFaqEditorImagesAction->execute($saved, (string) $saved->content);
            if ($syncedContent !== (string) $saved->content) {
                $saved->forceFill(['content' => $syncedContent])->save();
            }

            return $saved->fresh([
                'categories:id,name,domain,status,sort_order',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);
        });

        return [
            'faq' => FaqForStaffDetailDto::fromModel($updated)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        if (array_key_exists('question', $payload)) {
            $payload['question'] = trim((string) $payload['question']);
        }

        if (array_key_exists('content', $payload)) {
            $payload['content'] = $this->sanitizeEditorContent((string) $payload['content']);
        }

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
        $syncPayload = $categoryId > 0
            ? [$categoryId => ['is_primary' => true]]
            : [];

        $faq->categories()->sync($syncPayload);
    }
}
