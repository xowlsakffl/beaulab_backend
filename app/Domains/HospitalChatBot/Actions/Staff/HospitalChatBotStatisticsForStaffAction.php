<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotStatisticsForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotStatisticsForStaffAction
{
    public function __construct(private readonly HospitalChatBotStatisticsForStaffQuery $query) {}

    /**
     * @param  array{from:\Carbon\CarbonImmutable, to:\Carbon\CarbonImmutable}  $filters
     * @return array<string, mixed>
     */
    public function execute(array $filters): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_STATISTICS_SHOW);

        return $this->query->overview($filters['from'], $filters['to']);
    }

    public function live(): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_STATISTICS_SHOW);

        return $this->query->live();
    }
}
