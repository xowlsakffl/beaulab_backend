<?php

namespace App\Domains\VideoExposure\Dto;

use App\Domains\VideoExposure\Models\VideoExposure;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class VideoExposureDto
{
    public static function detail(VideoExposure $model): array
    {
        $model->loadMissing(VideoExposure::RELATIONS);

        return self::row($model);
    }

    public static function rows(iterable $entries, CarbonInterface $at, string $positionKey): array
    {
        $at = $at->copy()->startOfSecond();
        $entries = collect($entries);
        $models = VideoExposure::with(VideoExposure::RELATIONS)->whereIn('id', $entries->pluck('id'))->get()->keyBy('id');
        $clicks = DB::table('video_exposure_metrics')->where('position_key', $positionKey)->whereIn('source_key', $entries->pluck('source_key'))->pluck('click_count', 'source_key');

        return $entries->map(function ($entry) use ($models, $clicks, $at) {
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

            return [...self::row($model), 'exposure_status' => $state, 'active' => (bool) $entry->active, 'click_count' => (int) ($clicks[$entry->source_key] ?? 0)];
        })->filter()->values()->all();
    }

    private static function row(VideoExposure $model): array
    {
        $image = $model->video?->thumbnailMedia;

        return [
            'id' => $model->id, 'source_key' => 'video:'.$model->id,
            'video_id' => $model->hospital_video_id, 'name' => $model->video?->title ?? '연결 동영상 없음',
            'hospital_name' => $model->video?->hospital?->name,
            'image' => $image ? ['id' => $image->id, 'path' => $image->publicPath(), 'metadata' => $image->publicMetadata()] : null,
            'status' => $model->status, 'start_at' => $model->start_at?->toISOString(), 'end_at' => $model->end_at?->toISOString(),
            'manager_name' => $model->creator?->name, 'updated_at' => $model->updated_at?->toISOString(),
        ];
    }
}
