<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkCreateForStaffAction
{
    public function __construct(
        private readonly TalkCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Talk::class);

        $normalized = $this->normalizePayload($payload);

        $talk = DB::transaction(function () use ($normalized) {
            $talk = $this->query->create($normalized);

            $this->syncCategories($talk, $normalized['category_ids'] ?? []);
            $this->attachImages($talk, $normalized['images'] ?? []);
            $this->createAdminNoteIfRequested($talk, $normalized);

            return $talk->fresh([
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

    private function normalizePayload(array $payload): array
    {
        $payload['author_ip'] = request()->ip();

        return $payload;
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Talk $talk, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $syncPayload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($syncPayload === []) {
            return;
        }

        $talk->categories()->sync($syncPayload);
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    private function attachImages(Talk $talk, array $images): void
    {
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
