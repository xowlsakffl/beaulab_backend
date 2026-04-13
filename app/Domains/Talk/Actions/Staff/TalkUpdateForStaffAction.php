<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * TalkUpdateForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkUpdateForStaffAction
{
    public function __construct(
        private readonly TalkUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
    ) {}

    public function execute(Talk $talk, array $payload): array
    {
        Gate::authorize('update', $talk);

        $normalized = $payload;

        $talk = DB::transaction(function () use ($talk, $normalized) {
            $updated = $this->query->update($talk, $normalized);

            if (array_key_exists('category_ids', $normalized) && is_array($normalized['category_ids'])) {
                $this->syncCategories($updated, $normalized['category_ids']);
            }

            if (array_key_exists('images', $normalized) && is_array($normalized['images'])) {
                $this->replaceImages($updated, $normalized['images']);
            }

            $this->createAdminNoteIfRequested($updated, $normalized);

            return $updated->fresh([
                'author',
                'categories',
                'images',
                'adminNotes.creator',
            ]);
        });

        return [
            'talk' => TalkForStaffDetailDto::fromModel($talk)->toArray(),
        ];
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Talk $talk, array $categoryIds): void
    {
        $syncPayload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        $talk->categories()->sync($syncPayload);
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    private function replaceImages(Talk $talk, array $images): void
    {
        $this->mediaAttachAction->deleteCollectionMedia($talk, 'images');

        if ($images === []) {
            return;
        }

        $this->mediaAttachAction->attachMany(
            $talk,
            $images,
            'images',
            'hospital-talk',
            'images',
        );
    }

    private function createAdminNoteIfRequested(Talk $talk, array $payload): void
    {
        $note = trim((string) ($payload['admin_note'] ?? ''));
        if ($note === '') {
            return;
        }

        $actor = auth()->user();

        $this->adminNoteCreateAction->execute(
            $talk,
            $note,
            $actor instanceof AccountStaff ? $actor : null,
            true,
        );
    }
}
