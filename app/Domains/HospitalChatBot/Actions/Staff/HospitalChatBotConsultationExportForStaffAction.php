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
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

        return response()->streamDownload(function () use ($query, $chunkSize, $total): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                Log::error('챗봇 상담 기록 내보내기 실패', [
                    'event' => HospitalChatBotLog::EXPORT_FAILED,
                    'reason' => 'php://output 을 열 수 없었다',
                ]);

                return;
            }

            // BOM이 없으면 엑셀이 한국어를 깨뜨린다(PRD ADM-S06).
            fwrite($output, "\xEF\xBB\xBF");
            self::writeRow($output, [
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

            /*
             * cursor는 모델 객체를 한 번에 다 만들지 않는다. 다만 PDO MySQL이 기본
             * 버퍼드라 드라이버는 결과셋 전체를 이미 받아 둔 상태다. 즉 여기서 줄어드는
             * 것은 PHP 객체 쪽이고, 메모리 상한을 실제로 잡아 주는 것은 위의 max_rows다.
             * 진짜 스트리밍이 필요해지면 커넥션에 버퍼드 해제를 걸어야 한다.
             */
            /*
             * 스트림이 시작된 뒤의 예외는 예외 핸들러가 잡아도 이미 절반이 나간
             * 파일을 되돌릴 수 없다. 받는 사람은 잘린 CSV를 온전한 것으로 착각한다.
             * 그래서 여기서 직접 남기고, 파일 끝에 잘렸다는 표시를 붙인다.
             */
            $written = 0;

            try {
                foreach ($query->cursor()->chunk($chunkSize) as $chunk) {
                    foreach ($chunk as $consultation) {
                        self::writeRow($output, $this->row($consultation));
                        $written++;
                    }

                    fflush($output);
                }
            } catch (Throwable $exception) {
                Log::error('챗봇 상담 기록 내보내기 중단', [
                    'event' => HospitalChatBotLog::EXPORT_FAILED,
                    'exception' => $exception::class,
                    'reason' => $exception->getMessage(),
                    'written_rows' => $written,
                    'expected_rows' => $total,
                ]);

                self::writeRow($output, ["# 내보내기가 {$written}/{$total}행에서 중단됐습니다. 이 파일은 완전하지 않습니다."]);
            }

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
        // toImmutable을 거치는 이유는 mutable Carbon이 오면 startOfDay가 원본을 바꿔
        // $filters['to']가 23:59:59에서 00:00:00으로 되감기기 때문이다. 그러면
        // 종료일 하루가 통째로 빠진다.
        $days = (int) $from->toImmutable()->startOfDay()->diffInDays($to->toImmutable()->startOfDay()) + 1;
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
            self::text($consultation->hospital_name_snapshot) ?? "병원 #{$consultation->hospital_id}",
            self::text($consultation->requester_name_snapshot) ?? '',
            self::text($consultation->assigned_staff_name_snapshot) ?? '',
            $this->time($consultation->requested_at),
            $this->time($consultation->connected_at),
            $this->time($consultation->ended_at ?? $consultation->canceled_at),
            $this->waitingSeconds($consultation),
            $this->seconds($consultation->connected_at, $consultation->ended_at),
            HospitalChatBotConsultationStatus::label((string) $consultation->status),
            HospitalChatBotConsultationStatus::closeReasonLabel($consultation->close_reason),
            self::text($consultation->context_qna_question_snapshot) ?? '',
            self::text($consultation->source_page_path) ?? '',
        ];
    }

    /**
     * CSV 한 줄을 쓴다.
     *
     * escape를 비워 RFC 4180대로 쓴다. PHP 기본값인 백슬래시 escape는 표준이 아니라
     * 백슬래시 뒤에 큰따옴표가 오는 값을 리더가 다르게 읽는다. PHP 8.4부터는 생략
     * 자체가 deprecated이고 9에서 기본값이 ''로 바뀐다.
     *
     * @param  resource  $output
     * @param  array<int, string>  $values
     */
    private static function writeRow($output, array $values): void
    {
        fputcsv($output, $values, ',', '"', '');
    }

    /** 빈 문자열은 값이 없는 것으로 본다. 화면(DTO)도 같은 규칙을 쓴다. */
    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * 대기 시간. 아직 대기 중이면 지금까지를 센다.
     *
     * 화면(HospitalChatBotConsultationForStaffDto::waitingSeconds)과 같은 규칙이어야
     * 한다. 다르면 같은 상담이 목록과 파일에서 다른 값으로 보인다.
     */
    private function waitingSeconds(HospitalChatBotConsultation $consultation): string
    {
        $until = match ((string) $consultation->status) {
            HospitalChatBotConsultationStatus::WAITING => CarbonImmutable::now(),
            HospitalChatBotConsultationStatus::CANCELED => $consultation->canceled_at,
            default => $consultation->connected_at,
        };

        return $this->seconds($consultation->requested_at, $until);
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
