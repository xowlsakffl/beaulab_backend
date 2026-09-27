<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 상담 기록을 CSV로 내보낸다.
 *
 * 목록과 같은 필터를 그대로 받는다. 화면에서 본 것과 받은 파일이 다르면
 * 내보내기를 믿을 수 없게 된다(PRD ADM-S06).
 *
 * MVP는 동기 처리다. 기간과 행 수를 설정으로 막아 두고, 넘으면 범위를 줄이라고
 * 안내한다. 규모가 커지면 비동기 Job으로 옮긴다(PRD EX-025).
 */
final class HospitalChatBotConsultationExportForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForStaffQuery $query,
    ) {}

    /** @param array<string, mixed> $filters */
    public function execute(array $filters): StreamedResponse
    {
        $this->guardPeriod($filters);

        $query = $this->query->filtered($filters);

        $total = (clone $query)->count();
        $maxRows = (int) config('hospital_chatbot.export.max_rows');
        if ($total > $maxRows) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                "내보낼 상담이 {$total}건입니다. 한 번에 {$maxRows}건까지만 받을 수 있으니 기간이나 조건을 좁혀 주세요.",
            );
        }

        $chunkSize = (int) config('hospital_chatbot.export.chunk_size');

        return response()->streamDownload(function () use ($query, $chunkSize): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            // BOM이 없으면 엑셀이 한국어를 깨뜨린다(PRD ADM-S06).
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                '상담 ID',
                '병원',
                '요청자',
                '담당 상담원',
                '요청 시각',
                '연결 시각',
                '종료·취소 시각',
                '대기 시간(초)',
                '상담 시간(초)',
                '상태',
                '종료 사유',
                '상담 전 질문',
                '요청 화면',
            ]);

            // 정렬이 정해져 있으므로 chunk가 아니라 chunkById를 쓸 수 없다.
            // cursor는 커넥션 하나로 흘려보내 메모리를 잡아 두지 않는다.
            $rows = 0;
            foreach ($query->cursor()->chunk($chunkSize) as $chunk) {
                /** @var Collection<int, HospitalChatBotConsultation> $chunk */
                foreach ($chunk as $consultation) {
                    fputcsv($output, $this->row($consultation));
                    $rows++;
                }

                fflush($output);
            }

            unset($rows);
            fclose($output);
        }, $this->fileName($filters), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function guardPeriod(array $filters): void
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $maxDays = (int) config('hospital_chatbot.export.max_days');

        if (! $from instanceof CarbonInterface || ! $to instanceof CarbonInterface) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '내보낼 기간을 지정해 주세요.',
            );
        }

        // 같은 날을 고르면 1일이다. diffInDays는 0을 준다.
        $days = (int) $from->startOfDay()->diffInDays($to->startOfDay()) + 1;
        if ($days > $maxDays) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                "한 번에 {$maxDays}일까지만 내보낼 수 있습니다. 기간을 좁혀 주세요.",
            );
        }
    }

    /** @return array<int, string> */
    private function row(HospitalChatBotConsultation $consultation): array
    {
        return [
            (string) $consultation->id,
            (string) ($consultation->hospital_name_snapshot ?? "병원 #{$consultation->hospital_id}"),
            (string) ($consultation->requester_name_snapshot ?? ''),
            (string) ($consultation->assigned_staff_name_snapshot ?? ''),
            $this->time($consultation->requested_at),
            $this->time($consultation->connected_at),
            $this->time($consultation->ended_at ?? $consultation->canceled_at),
            $this->seconds($consultation->requested_at, $consultation->connected_at ?? $consultation->canceled_at),
            $this->seconds($consultation->connected_at, $consultation->ended_at),
            HospitalChatBotConsultationStatus::label((string) $consultation->status),
            HospitalChatBotConsultationStatus::closeReasonLabel($consultation->close_reason),
            (string) ($consultation->context_qna_question_snapshot ?? ''),
            (string) ($consultation->source_page_path ?? ''),
        ];
    }

    private function time(mixed $value): string
    {
        return $value instanceof CarbonInterface
            ? $value->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->format('Y-m-d H:i:s')
            : '';
    }

    /** 둘 중 하나라도 없으면 빈 칸으로 둔다. 0은 "정말 0초"일 때만 쓴다. */
    private function seconds(mixed $from, mixed $to): string
    {
        if (! $from instanceof CarbonInterface || ! $to instanceof CarbonInterface) {
            return '';
        }

        return (string) max(0, (int) $from->diffInSeconds($to, false));
    }

    /** @param array<string, mixed> $filters */
    private function fileName(array $filters): string
    {
        $format = static fn (mixed $value): string => $value instanceof CarbonInterface
            ? $value->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->format('Y-m-d')
            : CarbonImmutable::now(HospitalChatBotAvailabilityResolver::TIMEZONE)->format('Y-m-d');

        return sprintf('상담기록_%s_%s.csv', $format($filters['from'] ?? null), $format($filters['to'] ?? null));
    }
}
