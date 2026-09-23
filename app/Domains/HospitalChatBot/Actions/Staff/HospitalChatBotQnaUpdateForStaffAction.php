<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDuplicateForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaUpdateForStaffQuery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotQnaUpdateForStaffQuery $query,
        private readonly HospitalChatBotQnaDuplicateForStaffQuery $duplicateQuery,
    ) {}

    public function execute(HospitalChatBotQna $qna, array $payload): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        try {
            $updated = DB::transaction(function () use ($qna, $payload, $staffId): HospitalChatBotQna {
                $locked = HospitalChatBotQna::query()->lockForUpdate()->findOrFail($qna->getKey());
                $normalized = $this->normalizePayload($payload, $locked, $staffId);

                if ($this->duplicateQuery->exists((string) $normalized['question_key'], (int) $locked->id)) {
                    throw ValidationException::withMessages(['question' => '동일한 질문이 이미 등록되어 있습니다.']);
                }

                return $this->query->update($locked, $normalized);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['question' => '동일한 질문이 이미 등록되어 있습니다.']);
        }

        return HospitalChatBotQnaForStaffDto::fromModel($updated);
    }

    private function normalizePayload(array $payload, HospitalChatBotQna $qna, ?int $staffId): array
    {
        $normalized = ['updated_by_staff_id' => $staffId];

        foreach (['category', 'answer'] as $key) {
            if (array_key_exists($key, $payload)) {
                $normalized[$key] = trim((string) $payload[$key]);
            }
        }

        if (array_key_exists('question', $payload)) {
            $question = trim((string) $payload['question']);
            $normalized['question'] = $question;
            $normalized['question_key'] = HospitalChatBotQna::questionKey($question);
        } else {
            $normalized['question_key'] = (string) $qna->question_key;
        }

        if (array_key_exists('status', $payload)) {
            $normalized['status'] = (string) $payload['status'];
        }

        if (array_key_exists('sort_order', $payload)) {
            $normalized['sort_order'] = (int) $payload['sort_order'];
        }

        return $normalized;
    }
}
