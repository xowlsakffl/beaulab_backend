<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaListForStaffQuery;

final class HospitalChatBotQnaListForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        if ($filters['all'] ?? false) {
            return ['items' => $this->query->items($filters)->map(
                static fn (HospitalChatBotQna $qna): array => HospitalChatBotQnaForStaffDto::fromModel($qna),
            )->all(), 'meta' => null];
        }

        return PaginatedResponse::fromPaginator(
            $this->query->paginate($filters),
            static fn (HospitalChatBotQna $qna): array => HospitalChatBotQnaForStaffDto::fromModel($qna),
        );
    }
}
