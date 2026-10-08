<?php

namespace App\Domains\RemoteConsultation\Queries;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Builder;

final class RemoteConsultationMatchingQuery
{
    public function eligible(array $categoryIds, bool $requireBalance = true): Builder
    {
        $query = Hospital::query()->where('status', Hospital::STATUS_ACTIVE)->where('allow_status', Hospital::ALLOW_APPROVED)
            ->whereHas('accountHospital', fn ($q) => $q->where('status', 'ACTIVE'))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->with(['categories' => fn ($q) => $q->whereIn('categories.id', $categoryIds)]);
        if ($requireBalance) {
            $price = app(\App\Domains\ConsultationPricing\Support\ConsultationPricingResolver::class)->fixed('REMOTE')['price'];
            $query->whereHas('wallet', fn ($q) => $q->whereRaw('paid_balance - reserved_paid_balance + service_balance >= ?', [$price]));
        }

        return $query;
    }

    public function lockBalance(int $hospitalId, int $price): bool
    {
        $wallet = \App\Domains\HospitalWallet\Models\HospitalWallet::where('hospital_id', $hospitalId)->lockForUpdate()->first();

        return $wallet !== null && $wallet->availableTotalBalance() >= $price;
    }

    /** Eligibility stays fixed when random ranking is replaced with an AI ranker. */
    public function randomIds(array $categoryIds, array $excluded, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->eligible($categoryIds)->whereNotIn('id', $excluded)->inRandomOrder()->limit($limit)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
