<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Support\HospitalWalletHospitalIdParser;
use App\Domains\HospitalWallet\Support\HospitalWalletSms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalWalletNoticeListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = SmsBatch::query()
            ->with('actor')
            ->where('purpose', HospitalWalletSms::PURPOSE_BALANCE_NOTICE);

        $this->applySearch($builder, $filters['q'] ?? null);
        $builder->when(
            is_string($filters['status'] ?? null),
            fn (Builder $query) => $query->where('status', $filters['status']),
        );
        DateRangeFilter::apply(
            $builder,
            'sms_batches.created_at',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
        );

        return $builder
            ->orderByDesc('sms_batches.created_at')
            ->orderByDesc('sms_batches.id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    private function applySearch(Builder $builder, mixed $keyword): void
    {
        if (! is_string($keyword) || trim($keyword) === '') {
            return;
        }

        $keyword = trim($keyword);
        $numericId = ctype_digit($keyword) ? (int) $keyword : null;
        $hospitalId = HospitalWalletHospitalIdParser::fromKeyword($keyword);
        $normalizedPhone = preg_replace('/\D+/', '', $keyword) ?? '';

        $builder->where(function (Builder $query) use ($keyword, $numericId, $hospitalId, $normalizedPhone): void {
            if ($numericId !== null) {
                $query->where('sms_batches.id', $numericId);
            }

            $method = $numericId !== null ? 'orWhereHas' : 'whereHas';
            $query->{$method}('deliveries', function (Builder $deliveryQuery) use ($keyword, $hospitalId, $normalizedPhone): void {
                $deliveryQuery->where(function (Builder $targetQuery) use ($keyword, $hospitalId, $normalizedPhone): void {
                    if ($normalizedPhone !== '') {
                        $targetQuery->where('phone_normalized', 'like', "%{$normalizedPhone}%");
                    }

                    $hospitalMethod = $normalizedPhone !== '' ? 'orWhere' : 'where';
                    $targetQuery->{$hospitalMethod}(function (Builder $hospitalQuery) use ($keyword, $hospitalId): void {
                        $hospitalQuery
                            ->where('reference_type', (new Hospital)->getMorphClass())
                            ->where(function (Builder $referenceQuery) use ($keyword, $hospitalId): void {
                                $referenceQuery->where('reference_label', 'like', "%{$keyword}%");

                                if ($hospitalId !== null) {
                                    $referenceQuery->orWhere('reference_id', $hospitalId);
                                }
                            });
                    });
                });
            });
        });
    }
}
