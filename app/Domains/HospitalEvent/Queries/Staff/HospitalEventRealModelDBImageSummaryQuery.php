<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalEventRealModelDBImageSummaryQuery
{
    /**
     * @param  array<int, int|string>  $applicationIds
     * @return array<int, array{first_image: ?Media, image_count: int}>
     */
    public static function forApplicationIds(array $applicationIds): array
    {
        $applicationIds = collect($applicationIds)
            ->map(static fn (int|string $applicationId): int => (int) $applicationId)
            ->filter(static fn (int $applicationId): bool => $applicationId > 0)
            ->unique()
            ->values()
            ->all();

        if ($applicationIds === []) {
            return [];
        }

        $counts = Media::query()
            ->select('model_id')
            ->selectRaw('COUNT(*) AS image_count')
            ->where('model_type', HospitalEventRealModelDB::class)
            ->whereIn('model_id', $applicationIds)
            ->where('collection', HospitalEventRealModelDB::COLLECTION_IMAGES)
            ->groupBy('model_id')
            ->pluck('image_count', 'model_id');

        $rankedImageIds = Media::query()
            ->select(['id', 'model_id'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY model_id ORDER BY sort_order ASC, id ASC) AS image_rank')
            ->where('model_type', HospitalEventRealModelDB::class)
            ->whereIn('model_id', $applicationIds)
            ->where('collection', HospitalEventRealModelDB::COLLECTION_IMAGES);

        $firstImageIds = DB::query()
            ->fromSub($rankedImageIds, 'ranked_hospital_event_real_model_images')
            ->where('image_rank', 1)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        /** @var Collection<int, Media> $firstImages */
        $firstImages = Media::query()
            ->whereIn('id', $firstImageIds)
            ->get()
            ->keyBy('model_id');

        return collect($applicationIds)
            ->mapWithKeys(static fn (int $applicationId): array => [
                $applicationId => [
                    'first_image' => $firstImages->get($applicationId),
                    'image_count' => (int) ($counts[$applicationId] ?? 0),
                ],
            ])
            ->all();
    }
}
