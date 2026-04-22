<?php

namespace App\Domains\Talk\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Talk\Dto\User\TalkCreateForUserDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\User\TalkCreateForUserQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class TalkCreateForUserAction
{
    public function __construct(
        private readonly TalkCreateForUserQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        $normalized = $payload;
        $normalized['author_id'] = (int) $user->id;
        $normalized['author_ip'] = request()->ip();

        $talk = DB::transaction(function () use ($normalized): Talk {
            $talk = $this->query->create($normalized);

            $this->syncCategories($talk, $normalized['category_ids'] ?? []);
            $this->attachImages($talk, $normalized['images'] ?? []);

            return $talk->fresh([
                'author',
                'categories',
                'images',
            ]);
        });

        return [
            'talk' => TalkCreateForUserDto::fromModel($talk)->toArray(),
        ];
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
            'talk',
            'images',
        );
    }
}
