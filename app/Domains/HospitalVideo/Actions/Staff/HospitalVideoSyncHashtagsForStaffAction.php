<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoSyncHashtagsForStaffAction
{
    /**
     * @param  array<int, int|string>  $hashtagIds
     * @param  array<int, string>  $hashtagNames
     */
    public function execute(HospitalVideo $video, array $hashtagIds = [], array $hashtagNames = []): void
    {
        $beforeIds = $video->hashtags()
            ->pluck('hashtags.id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();

        $syncPayload = collect($this->resolveHashtagIds($hashtagIds, $hashtagNames))
            ->map(static fn (int|string $hashtagId): int => (int) $hashtagId)
            ->filter(static fn (int $hashtagId): bool => $hashtagId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $hashtagId, int $index): array => [
                $hashtagId => ['sort_order' => $index],
            ])
            ->all();

        $video->hashtags()->sync($syncPayload);
        Hashtag::syncUsageCounts([...$beforeIds, ...array_keys($syncPayload)]);
    }

    /**
     * @param  array<int, int|string>  $hashtagIds
     * @param  array<int, string>  $hashtagNames
     * @return array<int, int>
     */
    private function resolveHashtagIds(array $hashtagIds, array $hashtagNames): array
    {
        $ids = collect($hashtagIds)
            ->map(static fn (int|string $hashtagId): int => (int) $hashtagId)
            ->filter(static fn (int $hashtagId): bool => $hashtagId > 0);

        $nameIds = collect($hashtagNames)
            ->map(static fn (string $name): string => Hashtag::sanitizeName($name))
            ->filter(static fn (string $name): bool => $name !== '')
            ->unique(static fn (string $name): string => Hashtag::normalizeName($name))
            ->map(fn (string $name): int => (int) $this->findOrCreateActiveHashtag($name)->id);

        return $ids
            ->merge($nameIds)
            ->unique()
            ->values()
            ->all();
    }

    private function findOrCreateActiveHashtag(string $name): Hashtag
    {
        $normalizedName = Hashtag::normalizeName($name);
        $hashtag = Hashtag::query()
            ->where('normalized_name', $normalizedName)
            ->first();

        if (! $hashtag) {
            $data = [
                'name' => $name,
                'normalized_name' => $normalizedName,
            ];

            if (Hashtag::supportsStatus()) {
                $data['status'] = Hashtag::STATUS_ACTIVE;
            }

            if (Hashtag::supportsUsageCount()) {
                $data['usage_count'] = 0;
            }

            return Hashtag::create($data);
        }

        if ($hashtag->resolveStatus() !== Hashtag::STATUS_ACTIVE) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '비활성 해시태그는 사용할 수 없습니다.');
        }

        return $hashtag;
    }
}
