<?php

namespace App\Domains\ReviewExposure\Dto;

use App\Domains\HospitalReview\Queries\Staff\HospitalReviewImageSummaryQuery;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class ReviewExposureDto
{
    public static function detail(ReviewExposure $model): array
    {
        $model->loadMissing(ReviewExposure::RELATIONS);
        $images = HospitalReviewImageSummaryQuery::forReviewIds([$model->hospital_review_id]);

        return self::row($model, $images[$model->hospital_review_id] ?? []);
    }

    public static function rows(iterable $entries, CarbonInterface $at, string $positionKey): array
    {
        $at = $at->copy()->startOfSecond();
        $entries = collect($entries);
        $models = ReviewExposure::with(ReviewExposure::RELATIONS)->whereIn('id', $entries->pluck('id'))->get()->keyBy('id');
        $images = HospitalReviewImageSummaryQuery::forReviewIds($models->pluck('hospital_review_id')->all());
        $clicks = DB::table('review_exposure_metrics')->where('position_key', $positionKey)->whereIn('source_key', $entries->pluck('source_key'))->pluck('click_count', 'source_key');

        return $entries->map(function ($entry) use ($models, $images, $clicks, $at) {
            $model = $models->get($entry->id);
            if (! $model) {
                return null;
            }
            $state = match (true) {
                $model->end_at < $at => 'ENDED',
                $model->status !== 'ACTIVE' => 'PRIVATE',
                $model->start_at > $at => 'UPCOMING',
                ! $entry->active => 'SUSPENDED',
                default => 'ACTIVE',
            };

            return [...self::row($model, $images[$model->hospital_review_id] ?? []), 'exposure_status' => $state, 'active' => (bool) $entry->active, 'click_count' => (int) ($clicks[$entry->source_key] ?? 0)];
        })->filter()->values()->all();
    }

    private static function row(ReviewExposure $model, array $images): array
    {
        $image = $images['first_image'] ?? null;

        return [
            'id' => $model->id, 'source_key' => 'review:'.$model->id,
            'review_id' => $model->hospital_review_id, 'name' => $model->review?->title ?? '연결 후기 없음',
            'hospital_name' => $model->review?->hospital?->name,
            'image' => $image ? ['id' => $image->id, 'path' => $image->publicPath(), 'metadata' => $image->publicMetadata()] : null,
            'image_count' => (int) ($images['image_count'] ?? 0),
            'status' => $model->status, 'start_at' => $model->start_at?->toISOString(), 'end_at' => $model->end_at?->toISOString(),
            'manager_name' => $model->creator?->name, 'updated_at' => $model->updated_at?->toISOString(),
        ];
    }
}
