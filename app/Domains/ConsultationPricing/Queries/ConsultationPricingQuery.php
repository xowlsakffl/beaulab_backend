<?php

namespace App\Domains\ConsultationPricing\Queries;

use App\Domains\ConsultationPricing\Models\ConsultationPricingVersion;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class ConsultationPricingQuery
{
    public function versions(string $key)
    {
        return ConsultationPricingVersion::query()->whereIn('scope_id', DB::table('consultation_pricing_scopes')->select('id')->where('scope_key', $key))->whereNull('cancelled_at');
    }

    public function current(string $key, CarbonInterface $at): ?ConsultationPricingVersion
    {
        return $this->versions($key)->where('effective_at', '<=', $at)->orderByDesc('effective_at')->orderByDesc('id')->first();
    }

    public function pending(string $key, CarbonInterface $at): ?ConsultationPricingVersion
    {
        return $this->versions($key)->where('effective_at', '>', $at)->orderBy('effective_at')->first();
    }
}
