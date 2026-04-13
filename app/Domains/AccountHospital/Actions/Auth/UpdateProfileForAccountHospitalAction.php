<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Dto\Auth\ProfileForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Queries\Auth\UpdateProfileForAccountHospitalQuery;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class UpdateProfileForAccountHospitalAction
{
    public function __construct(
        private readonly UpdateProfileForAccountHospitalQuery $query,
    ) {}

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

        $hospital = $this->query->update($hospital, $filters);

        return [
            'profile' => ProfileForAccountHospitalDto::fromModel($hospital)->toArray(),
        ];
    }
}
