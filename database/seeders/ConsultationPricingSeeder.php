<?php

namespace Database\Seeders;

use App\Domains\ConsultationPricing\Models\ConsultationPricingVersion;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ConsultationPricingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['EVENT:SURGERY:0', 'EVENT:TREATMENT:0', 'EVENT:PROMOTION:0', 'REMOTE', 'REAL_MODEL'] as $key) {
            DB::transaction(function () use ($key) {
                DB::table('consultation_pricing_scopes')->insertOrIgnore(['scope_key' => $key, 'created_at' => now(), 'updated_at' => now()]);
                $scope = DB::table('consultation_pricing_scopes')->where('scope_key', $key)->lockForUpdate()->first();
                // 관리자가 설정한 값과 예약을 재실행 시 덮어쓰지 않는다.
                if (ConsultationPricingVersion::where('scope_id', $scope->id)->exists()) {
                    return;
                }
                $event = str_starts_with($key, 'EVENT:');
                ConsultationPricingVersion::create(['scope_id' => $scope->id, 'mode' => 'CUSTOM', 'ranges' => $event ? ConsultationPricingResolver::defaultRanges() : null, 'fixed_price' => $event ? null : 10000, 'effective_at' => now()]);
                DB::table('consultation_pricing_scopes')->where('id', $scope->id)->increment('lock_version');
            }, 3);
        }
    }
}
