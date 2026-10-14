<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotConsultationListForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query,
        private readonly \App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationReadForStaffQuery $reads,
    ) {}

    /** @param  array<string, mixed>  $filters */
    public function execute(array $filters): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_CONSULTATION_SHOW);

        if ($filters['feed'] ?? false) {
            $paginator = $this->query->feed($filters);

            return [
                'items' => $this->withUnread(collect($paginator->items())->map(fn ($item) => HospitalChatBotConsultationForStaffDto::fromModel($item))->all()),
                'meta' => [
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'status_counts' => $this->query->counts($filters),
                ],
            ];
        }
        $result = PaginatedResponse::fromPaginator(
            $this->query->paginate($filters),
            static fn (HospitalChatBotConsultation $consultation): array => HospitalChatBotConsultationForStaffDto::fromModel($consultation),
        );
        $result['meta']['status_counts'] = $this->query->counts($filters);

        $result['items'] = $this->withUnread($result['items']);

        return $result;
    }

    private function withUnread(array $items): array
    {
        $counts = $this->reads->counts(array_column($items, 'id'), (int) \App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor::current()->id);

        return array_map(static fn ($item) => $item + ['unread_count' => (int) ($counts[$item['id']] ?? 0)], $items);
    }
}
