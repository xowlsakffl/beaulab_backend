<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 상담 메시지 저장과 조회.
 *
 * 병원 사용자와 상담원이 같은 경로를 쓴다. 누가 보냈는지는 호출자가 결정해 넘긴다.
 */
final class HospitalChatBotConsultationMessageQuery
{
    /**
     * 메시지를 저장한다.
     *
     * 순번은 상담 행을 잠근 상태에서 매기므로 양쪽이 동시에 보내도 건너뛰거나 겹치지 않는다.
     * 같은 clientMessageId로 다시 들어오면 저장하지 않고 기존 메시지를 돌려준다(PRD EX-005).
     *
     * @return array{message: HospitalChatBotConsultationMessage, created: bool}
     */
    public function send(
        int $consultationId,
        string $senderType,
        ?int $senderId,
        ?string $senderName,
        string $content,
        ?string $clientMessageId,
    ): array {
        return DB::transaction(function () use (
            $consultationId,
            $senderType,
            $senderId,
            $senderName,
            $content,
            $clientMessageId,
        ): array {
            /** @var HospitalChatBotConsultation|null $consultation */
            $consultation = HospitalChatBotConsultation::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->first();

            if ($consultation === null) {
                throw new CustomException(ErrorCode::NOT_FOUND, '상담을 찾을 수 없습니다.');
            }

            if ((string) $consultation->status !== HospitalChatBotConsultationStatus::CONNECTED) {
                throw new CustomException(
                    ErrorCode::CONSULTATION_NOT_ACTIVE,
                    (string) $consultation->status === HospitalChatBotConsultationStatus::WAITING
                        ? '상담원이 연결된 뒤에 메시지를 보낼 수 있습니다.'
                        : '이미 종료된 상담에는 메시지를 보낼 수 없습니다.',
                );
            }

            $existing = $this->findByClientMessageId($consultationId, $senderType, $senderId, $clientMessageId);
            if ($existing !== null) {
                return ['message' => $existing, 'created' => false];
            }

            $now = CarbonImmutable::now();
            $sequence = (int) $consultation->last_message_sequence + 1;

            try {
                $message = HospitalChatBotConsultationMessage::query()->create([
                    'consultation_id' => $consultationId,
                    'sequence' => $sequence,
                    'sender_type' => $senderType,
                    'sender_id' => $senderId,
                    'sender_name_snapshot' => $senderName,
                    'client_message_id' => $clientMessageId,
                    'message_type' => HospitalChatBotConsultationMessage::TYPE_TEXT,
                    'content' => $content,
                    'broadcast_pending' => true,
                    'created_at' => $now,
                ]);
            } catch (QueryException $exception) {
                // 같은 clientMessageId가 동시에 두 번 들어온 경우. 먼저 저장된 쪽을 돌려준다.
                $concurrent = $this->isUniqueViolation($exception)
                    ? $this->findByClientMessageId($consultationId, $senderType, $senderId, $clientMessageId)
                    : null;

                if ($concurrent === null) {
                    // 멱등 충돌이 아닌 진짜 저장 실패다. 사람이 봐야 한다(PRD 16.6).
                    Log::error('챗봇 메시지 저장 실패', [
                        'event' => HospitalChatBotLog::MESSAGE_STORE_FAILED,
                        'exception' => $exception::class,
                        'reason' => $exception->getMessage(),
                        'consultation_id' => $consultationId,
                        'sequence' => $sequence,
                        'sender_type' => $senderType,
                    ]);

                    throw $exception;
                }

                return ['message' => $concurrent, 'created' => false];
            }

            // 모델 save()를 쓰면 HasAuditLogs가 메시지마다 감사 로그를 남긴다.
            // 상태 전이와 같이 쿼리 빌더로 갱신해 그 경로를 피한다.
            HospitalChatBotConsultation::query()
                ->whereKey($consultationId)
                ->update([
                    'last_message_sequence' => $sequence,
                    'last_message_at' => $now,
                    'updated_at' => $now,
                ]);

            return ['message' => $message, 'created' => true];
        });
    }

    /**
     * 상담의 메시지를 순번 오름차순으로 돌려준다.
     *
     * afterSequence를 주면 그 뒤의 것만 가져온다. 재접속 후 빠진 구간을 메울 때 쓴다.
     * 주지 않으면 가장 최근 것부터 limit만큼 가져와 뒤집는다. 채팅은 꼬리에서 열려야 하고,
     * 앞에서 잘라 내면 대화가 길 때 최근 메시지가 통째로 빠진다.
     *
     * @return Collection<int, HospitalChatBotConsultationMessage>
     */
    public function list(int $consultationId, ?int $afterSequence, int $limit): Collection
    {
        if ($afterSequence !== null) {
            return HospitalChatBotConsultationMessage::query()
                ->where('consultation_id', $consultationId)
                ->where('sequence', '>', $afterSequence)
                ->orderBy('sequence')
                ->limit($limit)
                ->get();
        }

        return HospitalChatBotConsultationMessage::query()
            ->where('consultation_id', $consultationId)
            ->orderByDesc('sequence')
            ->limit($limit)
            ->get()
            ->sortBy('sequence')
            ->values();
    }

    /** 돌려준 구간보다 앞에 더 있는지. 클라이언트가 "이전 대화 더 보기"를 띄울 근거가 된다. */
    public function hasOlderThan(int $consultationId, int $sequence): bool
    {
        return HospitalChatBotConsultationMessage::query()
            ->where('consultation_id', $consultationId)
            ->where('sequence', '<', $sequence)
            ->exists();
    }

    public function find(int $messageId): ?HospitalChatBotConsultationMessage
    {
        return HospitalChatBotConsultationMessage::query()->find($messageId);
    }

    private function findByClientMessageId(
        int $consultationId,
        string $senderType,
        ?int $senderId,
        ?string $clientMessageId,
    ): ?HospitalChatBotConsultationMessage {
        if ($clientMessageId === null) {
            return null;
        }

        return HospitalChatBotConsultationMessage::query()
            ->where('consultation_id', $consultationId)
            ->where('sender_type', $senderType)
            ->where('sender_id', $senderId)
            ->where('client_message_id', $clientMessageId)
            ->first();
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === 1062;
    }
}
