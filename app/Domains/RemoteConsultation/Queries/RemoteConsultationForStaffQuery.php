<?php

namespace App\Domains\RemoteConsultation\Queries;

use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RemoteConsultationForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q = RemoteConsultation::query()->with(['categories', 'media'])->withCount(['quotes', 'quotes as answer_count' => fn ($q) => $q->where('status', 'ANSWERED')]);
        foreach (['gender', 'approval_status'] as $key) {
            if (! empty($filters[$key])) {
                $q->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['start_date'])) {
            $q->where('created_at', '>=', CarbonImmutable::parse($filters['start_date'], 'Asia/Seoul')->startOfDay()->setTimezone(config('app.timezone')));
        }
        if (! empty($filters['end_date'])) {
            $q->where('created_at', '<', CarbonImmutable::parse($filters['end_date'], 'Asia/Seoul')->addDay()->startOfDay()->setTimezone(config('app.timezone')));
        }
        if (! empty($filters['birth_year_from'])) {
            $q->where('birth_date', '>=', $filters['birth_year_from'].'-01-01');
        }
        if (! empty($filters['birth_year_to'])) {
            $q->where('birth_date', '<=', $filters['birth_year_to'].'-12-31');
        }
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $like = '%'.addcslashes($term, '\\%_').'%';
            $q->where(function ($q) use ($term, $like) {
                $q->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                if (ctype_digit($term) && strlen($term) <= 15) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }

        $sort = in_array($filters['sort'] ?? null, ['id', 'created_at', 'answer_count', 'approval_status'], true)
            ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';
        if (empty($filters['sort'])) {
            $q->orderByRaw("CASE WHEN approval_status IN ('PENDING', 'REVIEWING') THEN 0 ELSE 1 END");
        }
        if ($sort === 'approval_status') {
            $q->orderByRaw("CASE approval_status WHEN 'PENDING' THEN 1 WHEN 'REVIEWING' THEN 2 WHEN 'APPROVED' THEN 3 WHEN 'REJECTED' THEN 4 ELSE 5 END ".$direction);
        } else {
            $q->orderBy($sort, $direction);
        }
        // Keep page boundaries deterministic when the primary values are equal.
        if ($sort !== 'id') {
            $q->orderBy('id', $direction);
        }

        return $q->paginate(15);
    }

    public function detail(int $id): RemoteConsultation
    {
        return RemoteConsultation::with(['categories', 'media', 'quotes', 'approver'])->findOrFail($id);
    }
}
