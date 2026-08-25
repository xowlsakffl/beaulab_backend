<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Dto\Hospital\AccountHospitalForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Queries\Hospital\ProfileUpdateForAccountHospitalQuery;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class ProfileUpdateForAccountHospitalAction
{
    public function __construct(
        private readonly ProfileUpdateForAccountHospitalQuery $query,
    ) {}

    /**
     * @param  array{name?:string}  $filters
     * @return array{profile: array}
     */
    public function execute(AccountHospital $hospital, array $filters): array
    {
        $hospital = $this->query->update($hospital, $filters);

        Log::info('병원 프로필 수정', [
            'hospital_id' => $hospital->id,
            'keys' => array_keys($filters),
        ]);

        return [
            'profile' => AccountHospitalForAccountHospitalDto::fromModel($hospital)->toArray(),
        ];
    }
}
