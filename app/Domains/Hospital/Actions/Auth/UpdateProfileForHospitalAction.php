<?php

namespace App\Domains\Hospital\Actions\Auth;

use App\Domains\Hospital\Dto\Auth\ProfileForHospitalDto;
use App\Domains\Hospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateProfileForHospitalAction
{
    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountHospital $hospital, array $filters): array
    {
        Log::info('파트너 프로필 수정', [
            'hospital_id' => $hospital->id,
            'keys' => array_keys($filters),
        ]);

        $hospital = DB::transaction(function () use ($hospital, $filters) {
            $hospital->fill($filters)->save();

            return $hospital->fresh();
        });

        return [
            'profile' => ProfileForHospitalDto::fromModel($hospital)->toArray(),
        ];
    }
}
