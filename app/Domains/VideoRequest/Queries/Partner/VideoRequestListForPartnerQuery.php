<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Models\VideoRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

final class VideoRequestListForPartnerQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        /** @var AccountPartner $actor */
        $actor = Auth::user();

        $builder = VideoRequest::query()->select([
            'id', 'hospital_id', 'beauty_id', 'doctor_id', 'expert_id',
            'title', 'review_status', 'is_usage_consented', 'duration_seconds',
            'requested_publish_start_at', 'requested_publish_end_at', 'is_publish_period_unlimited',
            'created_at', 'updated_at',
        ])->where('submitted_by_partner_id', $actor->id);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['review_status'] ?? null) && $filters['review_status'] !== []) {
            $builder->whereIn('review_status', $filters['review_status']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
