<?php

namespace App\Domains\BannerExposure\Queries;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\Notice\Models\Notice;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class BannerExposureTargetQuery
{
    public function categories(): Builder
    {
        return Category::query()->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('group_code', Category::GROUP_PROMOTION)->where('status', 'ACTIVE');
    }

    public function eligible(string $type, ?CarbonInterface $at = null): Builder
    {
        if ($type === 'HOSPITAL') {
            return Hospital::query()->where('allow_status', 'APPROVED')->where('status', 'ACTIVE');
        }
        if ($type === 'NOTICE') {
            return Notice::query()->forAudience(Notice::CHANNEL_APP_WEB)->where('status', 'ACTIVE');
        }
        $query = HospitalEvent::query()->where('allow_status', 'APPROVED')
            ->where('hospital_status', 'PUBLIC')->where('admin_status', 'NORMAL')
            ->whereHas('hospital', fn ($hospital) => $hospital->where('allow_status', 'APPROVED')->where('status', 'ACTIVE'));

        return $at ? $query->applicationOpen($at) : $query;
    }

    public function options(array $filters): array
    {
        $type = $filters['target_type'];
        $query = $type === 'CATEGORY' ? $this->categories() : $this->eligible($type);
        $name = $type === 'NOTICE' ? 'title' : 'name';
        if ($type === 'EVENT' && ! empty($filters['category_id'])) {
            $query->whereHas('categories', fn ($q) => $q->whereKey($filters['category_id']));
        }
        if ($type === 'EVENT') {
            $query->with('hospital:id,name');
        }
        if ($type === 'HOSPITAL') {
            $query->with('businessRegistration:id,hospital_id,business_number');
        }
        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function ($builder) use ($q, $name, $type): void {
                $builder->where($name, 'like', '%'.$q.'%');
                if (ctype_digit($q)) {
                    $builder->orWhere($builder->getModel()->qualifyColumn('id'), (int) $q);
                }
                if ($type === 'EVENT') {
                    $builder->orWhereHas('hospital', fn ($hospital) => $hospital->where('name', 'like', '%'.$q.'%'));
                }
                if ($type === 'HOSPITAL') {
                    $businessNumber = preg_replace('/[\s-]+/', '', $q);
                    if ($businessNumber !== '' && ctype_digit($businessNumber)) {
                        $builder->orWhereHas('businessRegistration', fn ($registration) => $registration
                            ->whereRaw("REPLACE(REPLACE(business_number, '-', ''), ' ', '') LIKE ?", ['%'.$businessNumber.'%']));
                    }
                }
            });
        }
        $page = $query->orderBy('id')->paginate(20, ['*'], 'page', $filters['page'] ?? 1);

        return [
            'items' => $page->map(fn ($row) => [
                'id' => $row->id, 'name' => $row->{$name},
                'hospital_name' => $type === 'EVENT' ? $row->hospital?->name : null,
                'business_number' => $type === 'HOSPITAL' ? $row->businessRegistration?->business_number : null,
                'start_at' => $type === 'EVENT' ? $row->event_start_at?->toISOString() : null,
                'end_at' => $type === 'EVENT' && ! $row->is_event_period_unlimited ? $row->event_end_at?->copy()->endOfDay()->toISOString() : null,
            ])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => 20, 'total' => $page->total()],
        ];
    }
}
