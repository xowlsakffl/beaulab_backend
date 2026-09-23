<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotAvailabilityForHospitalQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;

final class HospitalChatBotAvailabilityForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotAvailabilityForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(): array
    {
        return $this->resolver->resolve(
            now(HospitalChatBotAvailabilityResolver::TIMEZONE),
            $this->query->hours(),
            $this->query->closures(),
        );
    }
}
