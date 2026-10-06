<?php

namespace App\Domains\BannerExposure\Queries;

use App\Domains\BannerExposure\Models\BannerExposure;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class BannerExposureQuery
{
    public function __construct(private readonly BannerExposureTargetQuery $targets) {}

    public function activeManual(string $placement, CarbonInterface $now): Builder
    {
        $now = $now->copy()->startOfSecond();

        return BannerExposure::query()->where('placement', $placement)->where('status', 'ACTIVE')
            ->where('start_at', '<=', $now)->where('end_at', '>=', $now)
            ->where(function ($query) use ($now): void {
                $query->where('target_type', 'EXTERNAL');
                foreach (['HOSPITAL' => 'hospital_id', 'EVENT' => 'hospital_event_id', 'NOTICE' => 'notice_id'] as $type => $column) {
                    $target = $this->targets->eligible($type, $now);
                    $query->orWhere(fn ($branch) => $branch->where('target_type', $type)
                        ->whereIn($column, $target->select($target->getModel()->qualifyColumn('id'))));
                }
            })
            ->where(fn ($query) => $query->where('connection_type', 'PAGE')
                ->orWhere(fn ($branch) => $branch->whereIn('category_id', $this->targets->categories()->select('id'))
                    ->whereHas('event.categories', fn ($category) => $category->whereColumn('categories.id', 'banner_exposures.category_id'))));
    }

    public function entries(string $placement, CarbonInterface $now, array $filters = [])
    {
        $now = $now->copy()->startOfSecond();
        $eligible = $this->activeManual($placement, $now)->select('banner_exposures.id');
        $manual = BannerExposure::query()->where('placement', $placement)
            ->selectRaw("id, 'banner' as source, concat('banner:', id) as source_key, start_at, end_at, connection_type")
            ->selectRaw('case when id in ('.$eligible->toSql().') then 1 else 0 end as active', $eligible->getBindings());
        $ads = HospitalEventAd::query()->where('placement', $placement)->where('allow_status', 'APPROVED')
            ->selectRaw("id, 'ad' as source, concat('ad:', id) as source_key, start_at, end_at, 'AD' as connection_type")
            ->selectRaw('case when start_at <= ? and end_at >= ? then 1 else 0 end as active', [$now, $now]);
        if ($q = trim($filters['q'] ?? '')) {
            $manual->where(function ($builder) use ($q): void {
                $builder->where('title', 'like', '%'.$q.'%')
                    ->orWhereHas('creator', fn ($item) => $item->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('hospital', fn ($item) => $item->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('event', fn ($item) => $item->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('notice', fn ($item) => $item->where('title', 'like', '%'.$q.'%'));
                if (ctype_digit($q)) {
                    $builder->orWhere('banner_exposures.id', (int) $q);
                }
            });
            $ads->where(function ($builder) use ($q): void {
                $builder->whereHas('hospitalEvent', fn ($item) => $item->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('hospital', fn ($item) => $item->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('managerStaff', fn ($item) => $item->where('name', 'like', '%'.$q.'%'));
                if (ctype_digit($q)) {
                    $builder->orWhere('hospital_event_ads.id', (int) $q);
                }
            });
        }
        $query = DB::query()->fromSub($manual->toBase()->unionAll($ads->toBase()), 'entries');
        if (! empty($filters['connection_type'])) {
            $query->whereIn('connection_type', (array) $filters['connection_type']);
        }
        if (! empty($filters['start_date'])) {
            $query->where('end_at', '>=', $filters['start_date'].' 00:00:00');
        }
        if (! empty($filters['end_date'])) {
            $query->where('start_at', '<=', $filters['end_date'].' 23:59:59');
        }

        return $query;
    }

    public function placement(string $placement): object
    {
        return DB::table('banner_exposure_placements')->where('code', $placement)->firstOrFail();
    }

    public function activeKeys(string $placement, CarbonInterface $now): array
    {
        $order = json_decode($this->placement($placement)->ordered_keys ?? '[]', true);
        $keys = $this->entries($placement, $now)->where('active', 1)->orderBy('start_at')->orderBy('source')->orderBy('id')->pluck('source_key')->all();

        return [...array_values(array_intersect($order, $keys)), ...array_values(array_diff($keys, $order))];
    }
}
