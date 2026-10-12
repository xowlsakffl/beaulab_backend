<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 대시보드 집계(대시보드 PRD §9, §12.2).
 *
 * 상담 지표의 모집단은 전부 "기간 내 requested_at인 상담"이다. 상태나 종료 시각으로
 * 자르지 않는다. 그래야 카드끼리 분모가 같다. 이용 지표는 같은 기간의 이벤트다.
 * 두 출처를 한 숫자 안에서 섞지 않는다.
 */
final class HospitalChatBotDashboardForStaffQuery
{
    private const TOP_QNAS = 10;

    private const TOP_HOSPITALS = 5;

    /** @return array<string, mixed> */
    public function overview(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = (int) $from->diffInDays($to->startOfDay()) + 1;
        $previousFrom = $from->subDays($days);
        $previousTo = $from->subSecond();

        $summary = $this->summary($from, $to);
        $summary['requested_previous'] = (int) $this->consultations($previousFrom, $previousTo)->count();
        $summary['waiting_seconds'] = self::percentiles($this->waitingSeconds($from, $to));
        $summary['first_response_seconds'] = self::percentiles($this->firstResponseSeconds($from, $to));
        $summary['consultation_seconds'] = self::percentiles($this->consultationSeconds($from, $to));

        $events = $this->eventCounts($from, $to);
        $contextTop = $this->topContextQnas($from, $to);
        $viewedTop = $this->topViewedQnas($from, $to);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'timezone' => HospitalChatBotAvailabilityResolver::TIMEZONE,
            ],
            'summary' => $summary,
            'live' => $this->live(),
            'funnel' => [
                'collected_since' => $this->collectedSince(),
                'opened' => $events[HospitalChatBotEvent::TYPE_WIDGET_OPENED] ?? 0,
                'qna_viewed' => $events[HospitalChatBotEvent::TYPE_QNA_VIEWED] ?? 0,
                'requested' => $summary['requested'],
            ],
            'daily' => $this->daily($from, $to),
            'heatmap' => $this->heatmap($from, $to),
            'top_context_qnas' => $contextTop,
            'requested_without_context' => (int) $this->consultations($from, $to)->whereNull('context_qna_id')->count(),
            'top_viewed_qnas' => $viewedTop,
            'top_hospitals' => $this->topHospitals($from, $to),
        ];
    }

    private function consultations(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return HospitalChatBotConsultation::query()->whereBetween('requested_at', [$from, $to]);
    }

    private function events(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return HospitalChatBotEvent::query()->whereBetween('occurred_at', [$from, $to]);
    }

    /** @return array<string, int> */
    private function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $this->consultations($from, $to)
            ->selectRaw('COUNT(*) AS requested')
            ->selectRaw('SUM(connected_at IS NOT NULL) AS connected')
            ->selectRaw('SUM(close_reason = ?) AS timed_out', [HospitalChatBotConsultationStatus::REASON_WAITING_TIMEOUT])
            ->selectRaw('SUM(close_reason = ?) AS user_canceled', [HospitalChatBotConsultationStatus::REASON_USER_CANCELED])
            ->selectRaw('SUM(status IN (?, ?)) AS active', [
                HospitalChatBotConsultationStatus::WAITING,
                HospitalChatBotConsultationStatus::CONNECTED,
            ])
            ->first();

        return [
            'requested' => (int) ($row->requested ?? 0),
            'connected' => (int) ($row->connected ?? 0),
            'timed_out' => (int) ($row->timed_out ?? 0),
            'user_canceled' => (int) ($row->user_canceled ?? 0),
            'active' => (int) ($row->active ?? 0),
        ];
    }

    /** @return list<int> */
    private function waitingSeconds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        // 연결된 건만 잰다. 연결 안 된 건의 대기 시간은 "얼마나 기다리게 했나"가 아니라 놓친 건수다.
        return $this->consultations($from, $to)
            ->whereNotNull('connected_at')
            ->selectRaw('TIMESTAMPDIFF(SECOND, requested_at, connected_at) AS seconds')
            ->pluck('seconds')
            ->map(static fn ($value): int => (int) $value)
            ->all();
    }

    /** @return list<int> */
    private function firstResponseSeconds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        /*
         * 연결하면 자동 인사가 AGENT 메시지로 먼저 들어간다(greeting:{상담ID}). 그걸 세면
         * 첫 응답이 전부 0초다. 상담원이 직접 친 첫 메시지만 센다. 한마디도 안 한 건은 빠진다.
         */
        return DB::table('hospital_chatbot_consultations as c')
            ->join('hospital_chatbot_consultation_messages as m', static function ($join): void {
                $join->on('m.consultation_id', '=', 'c.id')
                    ->where('m.sender_type', HospitalChatBotConsultationMessage::SENDER_AGENT)
                    ->where('m.message_type', HospitalChatBotConsultationMessage::TYPE_TEXT)
                    ->where(static function (QueryBuilder $greeting): void {
                        $greeting->whereNull('m.client_message_id')
                            ->orWhere('m.client_message_id', 'not like', 'greeting:%');
                    });
            })
            ->whereBetween('c.requested_at', [$from, $to])
            ->whereNotNull('c.connected_at')
            ->groupBy('c.id', 'c.connected_at')
            ->selectRaw('TIMESTAMPDIFF(SECOND, c.connected_at, MIN(m.created_at)) AS seconds')
            ->pluck('seconds')
            ->map(static fn ($value): int => max(0, (int) $value))
            ->all();
    }

    /** @return list<int> */
    private function consultationSeconds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->consultations($from, $to)
            ->where('status', HospitalChatBotConsultationStatus::ENDED)
            ->whereNotNull('connected_at')
            ->whereNotNull('ended_at')
            ->selectRaw('TIMESTAMPDIFF(SECOND, connected_at, ended_at) AS seconds')
            ->pluck('seconds')
            ->map(static fn ($value): int => (int) $value)
            ->all();
    }

    /** @return array{waiting:int, longest_waiting_seconds:?int, checked_at:string} */
    private function live(): array
    {
        $now = CarbonImmutable::now(HospitalChatBotAvailabilityResolver::TIMEZONE);
        $waiting = HospitalChatBotConsultation::query()->where('status', HospitalChatBotConsultationStatus::WAITING);
        $oldest = (clone $waiting)->min('requested_at');

        return [
            'waiting' => (int) (clone $waiting)->count(),
            'longest_waiting_seconds' => $oldest === null
                ? null
                : max(0, (int) CarbonImmutable::parse((string) $oldest)->diffInSeconds($now, false)),
            'checked_at' => $now->toIso8601String(),
        ];
    }

    /** @return array<string, int> */
    private function eventCounts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->events($from, $to)
            ->groupBy('event_type')
            ->selectRaw('event_type, COUNT(*) AS total')
            ->pluck('total', 'event_type')
            ->map(static fn ($value): int => (int) $value)
            ->all();
    }

    private function collectedSince(): ?string
    {
        $first = HospitalChatBotEvent::query()->min('occurred_at');

        return $first === null
            ? null
            : CarbonImmutable::parse((string) $first)->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->toDateString();
    }

    /**
     * 날짜마다 한 줄. 요청이 없는 날도 0으로 채운다. 차트가 빈 날을 건너뛰면 추세가 거짓말을 한다.
     *
     * 네 구간은 서로 겹치지 않고 합치면 요청 수다: 종료(연결됐다 끝남), 대기 초과, 사용자 취소, 진행 중.
     *
     * @return list<array{date:string, ended:int, timed_out:int, user_canceled:int, active:int}>
     */
    private function daily(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->consultations($from, $to)
            ->selectRaw('DATE(requested_at) AS day')
            ->selectRaw('SUM(status = ?) AS ended', [HospitalChatBotConsultationStatus::ENDED])
            ->selectRaw('SUM(close_reason = ?) AS timed_out', [HospitalChatBotConsultationStatus::REASON_WAITING_TIMEOUT])
            ->selectRaw('SUM(close_reason = ?) AS user_canceled', [HospitalChatBotConsultationStatus::REASON_USER_CANCELED])
            ->selectRaw('SUM(status IN (?, ?)) AS active', [
                HospitalChatBotConsultationStatus::WAITING,
                HospitalChatBotConsultationStatus::CONNECTED,
            ])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        for ($day = $from->startOfDay(); $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $row = $rows->get($key);
            $daily[] = [
                'date' => $key,
                'ended' => (int) ($row->ended ?? 0),
                'timed_out' => (int) ($row->timed_out ?? 0),
                'user_canceled' => (int) ($row->user_canceled ?? 0),
                'active' => (int) ($row->active ?? 0),
            ];
        }

        return $daily;
    }

    /**
     * 요일(ISO, 월=1)×시(0~23). 0인 칸은 보내지 않는다. 화면이 7×24를 그린다.
     *
     * @return list<array{dow:int, hour:int, count:int}>
     */
    private function heatmap(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->consultations($from, $to)
            ->selectRaw('WEEKDAY(requested_at) + 1 AS dow, HOUR(requested_at) AS hour, COUNT(*) AS total')
            ->groupBy('dow', 'hour')
            ->orderBy('dow')->orderBy('hour')
            ->get()
            ->map(static fn ($row): array => ['dow' => (int) $row->dow, 'hour' => (int) $row->hour, 'count' => (int) $row->total])
            ->all();
    }

    /** @return list<array{qna_id:int, question:string, requested:int, viewed:int, deleted:bool}> */
    private function topContextQnas(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->consultations($from, $to)
            ->whereNotNull('context_qna_id')
            ->groupBy('context_qna_id')
            ->selectRaw('context_qna_id AS qna_id, COUNT(*) AS requested, MAX(context_qna_question_snapshot) AS snapshot')
            ->orderByDesc('requested')->orderBy('qna_id')
            ->limit(self::TOP_QNAS)
            ->get();

        $ids = $rows->pluck('qna_id')->map(static fn ($id): int => (int) $id)->all();
        $qnas = $this->qnasById($ids);
        $viewed = $this->viewedCountsByQna($from, $to, $ids);

        return $rows->map(static function ($row) use ($qnas, $viewed): array {
            $id = (int) $row->qna_id;
            $qna = $qnas->get($id);

            return [
                'qna_id' => $id,
                // 지금 질문 본문을 보여 준다. 고쳐졌으면 고친 쪽이 담당자가 찾을 이름이다. 없으면 요청 당시 사본.
                'question' => (string) ($qna?->question ?? $row->snapshot ?? ''),
                'requested' => (int) $row->requested,
                'viewed' => (int) ($viewed[$id] ?? 0),
                'deleted' => $qna === null || $qna->deleted_at !== null,
            ];
        })->all();
    }

    /** @return list<array{qna_id:int, question:string, viewed:int, requested:int, deleted:bool}> */
    private function topViewedQnas(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->events($from, $to)
            ->where('event_type', HospitalChatBotEvent::TYPE_QNA_VIEWED)
            ->whereNotNull('qna_id')
            ->groupBy('qna_id')
            ->selectRaw('qna_id, COUNT(*) AS viewed')
            ->orderByDesc('viewed')->orderBy('qna_id')
            ->limit(self::TOP_QNAS)
            ->get();

        $ids = $rows->pluck('qna_id')->map(static fn ($id): int => (int) $id)->all();
        $qnas = $this->qnasById($ids);
        $requested = $ids === [] ? [] : $this->consultations($from, $to)
            ->whereIn('context_qna_id', $ids)
            ->groupBy('context_qna_id')
            ->selectRaw('context_qna_id, COUNT(*) AS total')
            ->pluck('total', 'context_qna_id')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        return $rows->map(static function ($row) use ($qnas, $requested): array {
            $id = (int) $row->qna_id;
            $qna = $qnas->get($id);

            return [
                'qna_id' => $id,
                'question' => (string) ($qna?->question ?? ''),
                'viewed' => (int) $row->viewed,
                'requested' => (int) ($requested[$id] ?? 0),
                'deleted' => $qna === null || $qna->deleted_at !== null,
            ];
        })->all();
    }

    /** @return list<array{hospital_id:int, name:string, requested:int, connected:int, opened:int, qna_viewed:int}> */
    private function topHospitals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->consultations($from, $to)
            ->groupBy('hospital_id')
            ->selectRaw('hospital_id, COUNT(*) AS requested, SUM(connected_at IS NOT NULL) AS connected')
            // 이름은 요청 당시 사본 중 가장 최근 것. 계정·병의원이 바뀌어도 기록은 남아야 한다.
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(hospital_name_snapshot ORDER BY requested_at DESC SEPARATOR \'\\n\'), \'\\n\', 1) AS name')
            ->orderByDesc('requested')->orderBy('hospital_id')
            ->limit(self::TOP_HOSPITALS)
            ->get();

        $ids = $rows->pluck('hospital_id')->map(static fn ($id): int => (int) $id)->all();
        $events = $ids === [] ? collect() : $this->events($from, $to)
            ->whereIn('hospital_id', $ids)
            ->groupBy('hospital_id', 'event_type')
            ->selectRaw('hospital_id, event_type, COUNT(*) AS total')
            ->get()
            ->groupBy('hospital_id');

        return $rows->map(static function ($row) use ($events): array {
            $id = (int) $row->hospital_id;
            $byType = ($events->get($id) ?? collect())->pluck('total', 'event_type');

            return [
                'hospital_id' => $id,
                'name' => (string) ($row->name ?? "병의원 #{$id}"),
                'requested' => (int) $row->requested,
                'connected' => (int) $row->connected,
                'opened' => (int) ($byType[HospitalChatBotEvent::TYPE_WIDGET_OPENED] ?? 0),
                'qna_viewed' => (int) ($byType[HospitalChatBotEvent::TYPE_QNA_VIEWED] ?? 0),
            ];
        })->all();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, HospitalChatBotQna>
     */
    private function qnasById(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        // 삭제된 Q&A도 집계에는 나와야 한다. "삭제됨" 표시는 화면이 한다.
        return HospitalChatBotQna::withTrashed()->whereIn('id', $ids)->get(['id', 'question', 'deleted_at'])->keyBy('id');
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private function viewedCountsByQna(CarbonImmutable $from, CarbonImmutable $to, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->events($from, $to)
            ->where('event_type', HospitalChatBotEvent::TYPE_QNA_VIEWED)
            ->whereIn('qna_id', $ids)
            ->groupBy('qna_id')
            ->selectRaw('qna_id, COUNT(*) AS total')
            ->pluck('total', 'qna_id')
            ->map(static fn ($value): int => (int) $value)
            ->all();
    }

    /**
     * 중앙값·P90. MySQL 8에 PERCENTILE이 없어 정렬 뒤 순위로 고른다(nearest-rank).
     * 건수가 커지면 WINDOW 함수로 바꾼다.
     *
     * @param  list<int>  $values
     * @return array{median:?int, p90:?int, sample:int}
     */
    public static function percentiles(array $values): array
    {
        $count = count($values);
        if ($count === 0) {
            return ['median' => null, 'p90' => null, 'sample' => 0];
        }

        sort($values);
        $rank = static fn (float $ratio): int => $values[max(0, (int) ceil($ratio * $count) - 1)];

        return ['median' => $rank(0.5), 'p90' => $rank(0.9), 'sample' => $count];
    }
}
