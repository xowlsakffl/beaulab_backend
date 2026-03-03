<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Dto\Auth\ProfileForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateProfileForAccountHospitalAction
{
    /**
     * @param array{name?:string,email?:string} $filters
     * @return array{profile: array}
     */
    public function execute(AccountHospital $hospital, array $filters): array
    {
        Log::info('병원 프로필 수정', [
            'hospital_id' => $hospital->id,
            'keys' => array_keys($filters),
        ]);

        $hospital = DB::transaction(function () use ($hospital, $filters) {
            $hospital->fill($filters)->save();

            return $hospital->fresh();
        });

        return [
            'profile' => ProfileForAccountHospitalDto::fromModel($hospital)->toArray(),
        ];
    }
}
