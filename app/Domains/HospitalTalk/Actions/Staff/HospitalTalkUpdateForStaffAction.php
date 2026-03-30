<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkForStaffDetailDto;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalTalkUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
    ) {}

    public function execute(HospitalTalk $talk, array $payload): array
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
            'talk' => HospitalTalkForStaffDetailDto::fromModel($talk)->toArray(),
        ];
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(HospitalTalk $talk, array $categoryIds): void
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
    private function replaceImages(HospitalTalk $talk, array $images): void
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

    private function createAdminNoteIfRequested(HospitalTalk $talk, array $payload): void
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
