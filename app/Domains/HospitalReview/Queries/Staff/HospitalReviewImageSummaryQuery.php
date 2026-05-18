<?php

namespace App\Domains\HospitalReview\Queries\Staff;

use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalReviewImageSummaryQuery
{
    /**
     * @param  array<int, int|string>  $reviewIds
     * @return array<int, array{first_image: ?Media, image_count: int}>
     */
    public static function forReviewIds(array $reviewIds): array
    {
        $reviewIds = collect($reviewIds)
            ->map(static fn (int|string $reviewId): int => (int) $reviewId)
            ->filter(static fn (int $reviewId): bool => $reviewId > 0)
            ->unique()
            ->values()
            ->all();

        if ($reviewIds === []) {
            return [];
        }

        $counts = Media::query()
            ->select('model_id')
            ->selectRaw('COUNT(*) AS image_count')
            ->where('model_type', HospitalReview::class)
            ->whereIn('model_id', $reviewIds)
            ->whereIn('collection', ['before_images', 'after_images'])
            ->groupBy('model_id')
            ->pluck('image_count', 'model_id');

        $rankedImageIds = Media::query()
            ->select(['id', 'model_id'])
            ->selectRaw("ROW_NUMBER() OVER (PARTITION BY model_id ORDER BY CASE collection WHEN 'before_images' THEN 0 ELSE 1 END, sort_order ASC, id ASC) AS image_rank")
            ->where('model_type', HospitalReview::class)
            ->whereIn('model_id', $reviewIds)
            ->whereIn('collection', ['before_images', 'after_images']);

        $firstImageIds = DB::query()
            ->fromSub($rankedImageIds, 'ranked_hospital_review_images')
            ->where('image_rank', 1)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        /** @var Collection<int, Media> $firstImages */
        $firstImages = Media::query()
            ->whereIn('id', $firstImageIds)
            ->get()
            ->keyBy('model_id');

        return collect($reviewIds)
            ->mapWithKeys(static fn (int $reviewId): array => [
                $reviewId => [
                    'first_image' => $firstImages->get($reviewId),
                    'image_count' => (int) ($counts[$reviewId] ?? 0),
                ],
            ])
            ->all();
    }
}
