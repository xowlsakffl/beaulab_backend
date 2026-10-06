<?php

namespace App\Domains\EventExposure\Dto;

use App\Domains\EventExposure\Models\EventExposure;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class EventExposureDto
{
    public static function detail(EventExposure $model): array
    {
        $model->loadMissing(EventExposure::RELATIONS);

        return self::row($model, false);
    }

    public static function rows(iterable $entries, CarbonInterface $at, string $positionKey): array
    {
        $at = $at->copy()->startOfSecond();
        $entries = collect($entries);
        $manual = EventExposure::with(EventExposure::RELATIONS)->whereIn('id', $entries->where('source', 'event')->pluck('id'))->get()->keyBy('id');
        $ads = HospitalEventAd::with(['hospitalEvent.thumbnailImage', 'hospitalEvent.hospital:id,name', 'managerStaff:id,name'])->whereIn('id', $entries->where('source', 'ad')->pluck('id'))->get()->keyBy('id');

        $clicks = DB::table('event_exposure_metrics')->where('position_key', $positionKey)
            ->whereIn('source_key', $entries->pluck('source_key'))->pluck('click_count', 'source_key');

        return $entries->map(function ($entry) use ($manual, $ads, $at, $clicks) {
            $ad = $entry->source === 'ad';
            $model = ($ad ? $ads : $manual)->get($entry->id);
            if (! $model) {
                return null;
            }
            $state = match (true) {
                $model->end_at < $at => 'ENDED',
                ! $ad && $model->status !== 'ACTIVE' => 'PRIVATE',
                $model->start_at > $at => 'UPCOMING',
                ! $entry->active => 'SUSPENDED',
                default => 'ACTIVE',
            };

            return [...self::row($model, $ad), 'exposure_status' => $state, 'active' => (bool) $entry->active,
                'click_count' => (int) ($clicks[$entry->source_key] ?? 0)];
        })->filter()->values()->all();
    }

    private static function row($model, bool $ad): array
    {
        $event = $ad ? $model->hospitalEvent : $model->event;
        $image = $event?->thumbnailImage;

        return [
            'id' => $model->id, 'source_key' => ($ad ? 'ad:' : 'event:').$model->id, 'is_ad' => $ad,
            'event_id' => $model->hospital_event_id, 'name' => $event?->name ?? '연결 이벤트 없음', 'hospital_name' => $event?->hospital?->name,
            'image' => $image ? ['id' => $image->id, 'path' => $image->publicPath(), 'file_name' => basename($image->path)] : null,
            'status' => $ad ? 'ACTIVE' : $model->status,
            'start_at' => $model->start_at?->toISOString(), 'end_at' => $model->end_at?->toISOString(),
            'event_start_at' => $event?->event_start_at?->toISOString(),
            'event_end_at' => $event && ! $event->is_event_period_unlimited ? $event->event_end_at?->copy()->endOfDay()->toISOString() : null,
            'manager_name' => $ad ? $model->managerStaff?->name : $model->creator?->name, 'updated_at' => $model->updated_at?->toISOString(),
        ];
    }
}
