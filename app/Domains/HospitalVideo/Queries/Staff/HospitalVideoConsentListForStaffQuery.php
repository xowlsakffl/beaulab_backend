<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalVideoConsentListForStaffQuery
{
    public const SORT_FIELDS = ['id', 'name', 'department', 'total_video_count', 'visible_video_count', 'video_consented_at', 'status'];

    public function __construct(private readonly HospitalVideoSummaryForStaffQuery $summaryQuery) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $videos = HospitalVideo::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('hospital_videos.hospital_id', 'hospitals.id');
        $visibleVideos = clone $videos;
        $this->summaryQuery->applySummaryFilter($visibleVideos, HospitalVideo::SUMMARY_FILTER_NORMAL);

        $query = Hospital::query()
            ->select(['id', 'name', 'department', 'status', 'video_consented_at'])
            ->selectSub($videos, 'total_video_count')
            ->selectSub($visibleVideos, 'visible_video_count');

        foreach (['department', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $query->whereIn($key, $filters[$key]);
            }
        }

        DateRangeFilter::apply($query, 'video_consented_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('name', 'like', '%'.$keyword.'%');
                if (preg_match('/^(?:HID[-_ ]?)?(\d+)$/i', $keyword, $matches)) {
                    $query->orWhere('id', $matches[1]);
                }
            });
        }

        $sort = in_array($filters['sort'] ?? null, self::SORT_FIELDS, true) ? $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);
        if ($sort !== 'id') {
            $query->orderByDesc('id');
        }

        return $query->paginate(
            perPage: min(100, max(1, (int) ($filters['per_page'] ?? 15))),
            page: max(1, (int) ($filters['page'] ?? 1)),
        );
    }
}
