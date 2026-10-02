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
            ->with(['hospital.logoMedia', 'latestMessage'])
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
    public function feed(array $filters): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        return $this->filtered($filters)->reorder()
            ->with(['hospital.logoMedia', 'latestMessage'])
            ->select('hospital_chatbot_consultations.*')
            ->selectRaw("(CASE status WHEN 'WAITING' THEN 0 WHEN 'CONNECTED' THEN 1 ELSE 2 END) AS feed_group")
            ->selectRaw("CASE WHEN status = 'WAITING' THEN -COALESCE(UNIX_TIMESTAMP(requested_at), 0) WHEN status = 'CONNECTED' THEN COALESCE(UNIX_TIMESTAMP(last_message_at), UNIX_TIMESTAMP(connected_at), UNIX_TIMESTAMP(requested_at), 0) ELSE COALESCE(UNIX_TIMESTAMP(ended_at), UNIX_TIMESTAMP(canceled_at), UNIX_TIMESTAMP(requested_at), 0) END AS feed_time")
            ->orderBy('feed_group')->orderByDesc('feed_time')->orderByDesc('id')
            ->cursorPaginate((int) $filters['per_page']);
    }

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

        if (! empty($filters['mine_staff_id'])) {
            $query->where(function (Builder $scope) use ($filters) {
                $scope->where('assigned_staff_id', (int) $filters['mine_staff_id'])
                    ->orWhere(function (Builder $waiting) {
                        $waiting->where('status', HospitalChatBotConsultationStatus::WAITING)
                            ->whereNull('assigned_staff_id');
                    });
            });
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

        if (($filters['q'] ?? null) !== null) {
            $term = $filters['q'];
            $query->where(function ($search) use ($term) {
                $search->where('hospital_name_snapshot', 'like', '%'.self::escapeLike($term).'%')
                    ->orWhereHas('hospital', fn ($hospital) => $hospital->where('name', 'like', '%'.self::escapeLike($term).'%'));
                if (ctype_digit($term) && strlen($term) <= 15) {
                    $search->orWhere('hospital_id', (int) $term);
                }
            });
        }
        if (! empty($filters['close_reason'])) {
            $query->where('close_reason', $filters['close_reason']);
        }
        if (($filters['sort'] ?? null) === 'manage') {
            return $query
                ->orderByRaw("CASE status WHEN 'WAITING' THEN 0 WHEN 'CONNECTED' THEN 1 ELSE 2 END")
                ->orderByRaw("CASE WHEN status = 'WAITING' THEN requested_at END ASC")
                ->orderByRaw("CASE WHEN status = 'CONNECTED' THEN COALESCE(last_message_at, connected_at, requested_at) ELSE COALESCE(ended_at, canceled_at, requested_at) END DESC")
                ->orderByDesc('id');
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
     * 상태 범위와 페이지에 관계없이 나머지 검색 조건의 건수를 집계한다.
     */
    public function counts(array $filters): array
    {
        $filters['status'] = [];
        $rows = $this->filtered($filters)->reorder()->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $active = (int) ($rows['WAITING'] ?? 0) + (int) ($rows['CONNECTED'] ?? 0);
        $closed = (int) ($rows['ENDED'] ?? 0) + (int) ($rows['CANCELED'] ?? 0);

        return [
            'all' => $active + $closed,
            'active' => $active,
            'closed' => $closed,
            'waiting' => (int) ($rows['WAITING'] ?? 0),
            'connected' => (int) ($rows['CONNECTED'] ?? 0),
            'ended' => (int) ($rows['ENDED'] ?? 0),
            'canceled' => (int) ($rows['CANCELED'] ?? 0),
        ];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function find(int $consultationId): HospitalChatBotConsultation
    {
        $consultation = HospitalChatBotConsultation::query()->with('hospital.logoMedia')->find($consultationId);

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
