<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalWalletListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $at = now();

        $activeEventCount = HospitalEvent::query()
            ->selectRaw('count(*)')
            ->whereColumn('hospital_events.hospital_id', 'hospital_wallets.hospital_id')
            ->applicationOpen($at);

        $activeAdCount = HospitalEventAd::query()
            ->selectRaw('count(*)')
            ->whereColumn('hospital_event_ads.hospital_id', 'hospital_wallets.hospital_id')
            ->running($at)
            ->whereHas('hospitalEvent', fn (Builder $query): Builder => $query->applicationOpen($at));

        $builder = HospitalWallet::query()
            ->select([
                'hospital_wallets.id',
                'hospital_wallets.hospital_id',
                'hospital_wallets.paid_balance',
                'hospital_wallets.service_balance',
                'hospital_wallets.last_transaction_at',
            ])
            ->addSelect([
                'active_event_count' => $activeEventCount,
                'active_ad_count' => $activeAdCount,
            ])
            ->whereHas('hospital')
            ->with('hospital:id,name');

        $this->applySearch($builder, $filters['q'] ?? null);
        $this->applySort(
            $builder,
            $filters['sort'] ?? null,
            ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        );

        return $builder
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    private function applySearch(Builder $builder, mixed $keyword): void
    {
        if (! is_string($keyword) || trim($keyword) === '') {
            return;
        }

        $keyword = trim($keyword);
        $hospitalId = $this->hospitalIdFromKeyword($keyword);

        $builder->whereHas('hospital', function (Builder $query) use ($keyword, $hospitalId): void {
            $query->where('name', 'like', "%{$keyword}%");

            if ($hospitalId !== null) {
                $query->orWhere('id', $hospitalId);
            }
        });
    }

    private function applySort(Builder $builder, mixed $sort, string $direction): void
    {
        if (! is_string($sort)) {
            $builder->orderByDesc('hospital_wallets.hospital_id');

            return;
        }

        match ($sort) {
            'hospital_id' => $builder->orderBy('hospital_wallets.hospital_id', $direction),
            'hospital_name' => $builder->orderBy(
                fn ($query) => $query
                    ->select('name')
                    ->from('hospitals')
                    ->whereColumn('hospitals.id', 'hospital_wallets.hospital_id')
                    ->limit(1),
                $direction,
            ),
            'total_balance' => $builder->orderByRaw(
                '(hospital_wallets.paid_balance + hospital_wallets.service_balance) '.$direction,
            ),
            'paid_balance', 'service_balance', 'last_transaction_at' => $builder->orderBy(
                "hospital_wallets.{$sort}",
                $direction,
            ),
            'active_event_count', 'active_ad_count' => $builder->orderBy($sort, $direction),
            default => $builder->orderByDesc('hospital_wallets.hospital_id'),
        };

        if ($sort !== 'hospital_id') {
            $builder->orderByDesc('hospital_wallets.hospital_id');
        }
    }

    private function hospitalIdFromKeyword(string $keyword): ?int
    {
        if (ctype_digit($keyword)) {
            return (int) $keyword;
        }

        if (preg_match('/^HID[-_ ]?(\d+)$/i', $keyword, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
