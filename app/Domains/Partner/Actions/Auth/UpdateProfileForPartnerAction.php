<?php

namespace App\Domains\Partner\Actions\Auth;

use App\Domains\Partner\Dto\Auth\ProfileForPartnerDto;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateProfileForPartnerAction
{
    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountPartner $partner, array $filters): array
    {
        Log::info('파트너 프로필 수정', [
            'partner_id' => $partner->id,
            'keys' => array_keys($filters),
        ]);

        $partner = DB::transaction(function () use ($partner, $filters) {
            $partner->fill($filters)->save();

            return $partner->fresh();
        });

        return [
            'profile' => ProfileForPartnerDto::fromModel($partner)->toArray(),
        ];
    }
}
