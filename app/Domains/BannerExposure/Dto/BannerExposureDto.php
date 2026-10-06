<?php

namespace App\Domains\BannerExposure\Dto;

use App\Domains\BannerExposure\Models\BannerExposure;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class BannerExposureDto
{
    public static function rows(iterable $entries, CarbonInterface $now): array
    {
        $now = $now->copy()->startOfSecond();
        $entries = collect($entries);
        $manual = BannerExposure::with(BannerExposure::RELATIONS)->whereIn('id', $entries->where('source', 'banner')->pluck('id'))->get()->keyBy('id');
        $ads = HospitalEventAd::with(['adImage', 'hospitalEvent:id,name', 'hospital:id,name', 'managerStaff:id,name'])
            ->whereIn('id', $entries->where('source', 'ad')->pluck('id'))->get()->keyBy('id');
        $clicks = DB::table('banner_exposure_metrics')->whereIn('source_key', $entries->pluck('source_key'))->pluck('click_count', 'source_key');

        return $entries->map(function ($entry) use ($manual, $ads, $clicks, $now): ?array {
            $ad = $entry->source === 'ad';
            $model = $ad ? $ads->get($entry->id) : $manual->get($entry->id);
            if (! $model) {
                return null;
            }
            $data = $ad ? [
                'id' => $model->id, 'placement' => $model->placement, 'title' => $model->hospitalEvent?->name ?? '연결 이벤트 없음',
                'connection_type' => 'AD', 'target_type' => 'EVENT', 'target_id' => $model->hospital_event_id,
                'target_name' => $model->hospitalEvent?->name, 'hospital_name' => $model->hospital?->name,
                'status' => 'ACTIVE', 'image' => self::image($model->adImage), 'manager_name' => $model->managerStaff?->name,
                'start_at' => $model->start_at?->toISOString(), 'end_at' => $model->end_at?->toISOString(),
                'updated_at' => $model->updated_at?->toISOString(), 'open_mode' => 'IN_APP', 'external_url' => null,
                'category_id' => null, 'category_name' => null,
            ] : self::detail($model);
            $progress = $model->start_at > $now ? 'UPCOMING' : ($model->end_at < $now ? 'ENDED' : 'CURRENT');
            $exposureStatus = match (true) {
                $model->end_at < $now => 'ENDED',
                ! $ad && $model->status !== 'ACTIVE' => 'PRIVATE',
                $model->start_at > $now => 'UPCOMING',
                ! $entry->active => 'SUSPENDED',
                default => 'ACTIVE',
            };

            return [...$data, 'source_key' => $entry->source_key, 'active' => (bool) $entry->active,
                'exposure_status' => $exposureStatus,
                'progress' => $progress, 'click_count' => (int) ($clicks[$entry->source_key] ?? 0),
                'reason' => match ($exposureStatus) {
                    'UPCOMING' => '노출예정', 'ENDED' => '노출종료',
                    'PRIVATE' => '비공개', 'SUSPENDED' => '노출중지', default => null,
                },
            ];
        })->filter()->values()->all();
    }

    public static function detail(BannerExposure $banner): array
    {
        return [
            'id' => $banner->id, 'placement' => $banner->placement, 'title' => $banner->title,
            'connection_type' => $banner->connection_type, 'target_type' => $banner->target_type,
            'target_id' => $banner->hospital_id ?? $banner->hospital_event_id ?? $banner->notice_id,
            'target_name' => $banner->hospital?->name ?? $banner->event?->name ?? $banner->notice?->title,
            'category_id' => $banner->category_id, 'category_name' => $banner->category?->name,
            'external_url' => $banner->external_url, 'open_mode' => $banner->open_mode,
            'status' => $banner->status, 'image' => self::image($banner->image),
            'start_at' => $banner->start_at->toISOString(), 'end_at' => $banner->end_at->toISOString(),
            'manager_name' => $banner->creator?->name, 'updated_at' => $banner->updated_at?->toISOString(),
        ];
    }

    private static function image($media): ?array
    {
        return $media ? ['id' => $media->id, 'path' => $media->publicPath(), 'file_name' => basename($media->path)] : null;
    }
}
