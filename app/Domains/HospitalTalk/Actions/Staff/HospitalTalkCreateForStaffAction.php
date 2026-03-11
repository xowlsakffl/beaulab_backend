<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkForStaffDetailDto;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkCreateForStaffAction
{
    public function __construct(
        private readonly HospitalTalkCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalTalk::class);

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
            'talk' => HospitalTalkForStaffDetailDto::fromModel($talk)->toArray(),
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
    private function syncCategories(HospitalTalk $talk, array $categoryIds): void
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
    private function attachImages(HospitalTalk $talk, array $images): void
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

    private function createAdminNoteIfRequested(HospitalTalk $talk, array $payload): void
    {
        $note = trim((string) ($payload['admin_note'] ?? ''));
        if ($note === '') {
            return;
        }

        $actor = auth()->user();
        $createdByStaffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $this->adminNoteCreateAction->execute($talk, $note, $createdByStaffId, true);
    }
}
