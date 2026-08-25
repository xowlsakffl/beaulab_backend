<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Dto\Hospital\HospitalAccountInvitationForHospitalDto;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountInvitationForHospitalQuery;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;

final class HospitalAccountInvitationGetForHospitalAction
{
    public function __construct(
        private readonly HospitalAccountInvitationForHospitalQuery $query,
    ) {}

    /** @return array{invitation: array} */
    public function execute(string $token): array
    {
        $invitation = HospitalAccountInvitationGuard::assertActive(
            $this->query->findByTokenHash(HospitalAccountInvitationToken::hash($token))
        );

        return [
            'invitation' => HospitalAccountInvitationForHospitalDto::fromModel($invitation),
        ];
    }
}
