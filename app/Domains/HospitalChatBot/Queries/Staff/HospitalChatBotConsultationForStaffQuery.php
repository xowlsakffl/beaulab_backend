<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationStatusHistory;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class HospitalChatBotConsultationForStaffQuery
{
    public function __construct(
        private readonly HospitalChatBotConsultationTransition $transition,
    ) {}

    /**
     * @param  array{status?:list<string>, assigned_staff_id?:?int, per_page:int}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = HospitalChatBotConsultation::query();

        $statuses = $filters['status'] ?? [];
        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if (! empty($filters['assigned_staff_id'])) {
            $query->where('assigned_staff_id', (int) $filters['assigned_staff_id']);
        }

        // 대기가 먼저, 그 안에서는 오래 기다린 순. 상담원이 위에서부터 집으면 된다.
        return $query
            ->orderByRaw('FIELD(status, ?, ?, ?, ?)', [
                HospitalChatBotConsultationStatus::WAITING,
                HospitalChatBotConsultationStatus::CONNECTED,
                HospitalChatBotConsultationStatus::ENDED,
                HospitalChatBotConsultationStatus::CANCELED,
            ])
            ->orderBy('requested_at')
            ->orderBy('id')
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }

    public function find(int $consultationId): HospitalChatBotConsultation
    {
        $consultation = HospitalChatBotConsultation::query()->find($consultationId);

        if ($consultation === null) {
            throw new CustomException(ErrorCode::NOT_FOUND, '상담을 찾을 수 없습니다.');
        }

        return $consultation;
    }

    /** @return Collection<int, HospitalChatBotConsultationStatusHistory> */
    public function histories(int $consultationId): Collection
    {
        return HospitalChatBotConsultationStatusHistory::query()
            ->where('consultation_id', $consultationId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    public function claim(int $consultationId, int $staffId, ?string $staffName): bool
    {
        return $this->transition->claim($consultationId, $staffId, $staffName);
    }

    public function end(int $consultationId, int $staffId): bool
    {
        return $this->transition->end($consultationId, $staffId);
    }
}
