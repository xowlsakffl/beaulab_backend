<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationStatusHistory;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationFilters;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class HospitalChatBotConsultationForStaffQuery
{
    public function __construct(
        private readonly HospitalChatBotConsultationTransition $transition,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }

    /**
     * 목록과 내보내기가 같은 조건을 본다.
     *
     * 내보내기가 자기 조건을 따로 만들면 화면에서 본 것과 받은 파일이 달라진다.
     * PRD가 "현재 필터 조건을 기준으로"라고 못 박은 지점이다(ADM-S06).
     *
     * @param  array<string, mixed>  $filters
     */
    public function filtered(array $filters): Builder
    {
        $query = HospitalChatBotConsultation::query();

        $statuses = $filters['status'] ?? [];
        if (is_array($statuses) && $statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        foreach (['assigned_staff_id' => 'assigned_staff_id', 'hospital_id' => 'hospital_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, (int) $filters[$key]);
            }
        }

        if (! empty($filters['consultation_id'])) {
            $query->whereKey((int) $filters['consultation_id']);
        }

        if (! empty($filters['requester'])) {
            // 요청 당시 표시명으로 찾는다. 계정을 지워도 기록은 남아야 하므로
            // 조인이 아니라 스냅샷을 본다.
            $query->where(
                'requester_name_snapshot',
                'like',
                '%'.self::escapeLike((string) $filters['requester']).'%',
            );
        }

        if (($filters['from'] ?? null) instanceof CarbonInterface) {
            $query->where('requested_at', '>=', $filters['from']);
        }

        if (($filters['to'] ?? null) instanceof CarbonInterface) {
            $query->where('requested_at', '<=', $filters['to']);
        }

        return ($filters['sort'] ?? HospitalChatBotConsultationFilters::SORT_QUEUE) === HospitalChatBotConsultationFilters::SORT_RECENT
            // 기록 조회는 최근 것부터 본다(ADM-S05).
            ? $query->orderByDesc('requested_at')->orderByDesc('id')
            // 대기 목록은 집어야 할 순서다. 대기가 먼저, 그 안에서는 오래 기다린 순.
            : $query
                ->orderByRaw('FIELD(status, ?, ?, ?, ?)', [
                    HospitalChatBotConsultationStatus::WAITING,
                    HospitalChatBotConsultationStatus::CONNECTED,
                    HospitalChatBotConsultationStatus::ENDED,
                    HospitalChatBotConsultationStatus::CANCELED,
                ])
                ->orderBy('requested_at')
                ->orderBy('id');
    }

    /**
     * LIKE 안에서 %와 _는 와일드카드다. 사용자가 친 그대로 찾게 이스케이프한다.
     */
    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
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
