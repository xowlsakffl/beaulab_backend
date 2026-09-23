<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaCreateForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDuplicateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaCreateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotQnaCreateForStaffQuery $query,
        private readonly HospitalChatBotQnaDuplicateForStaffQuery $duplicateQuery,
    ) {}

    public function execute(array $payload): array
    {
        $question = trim((string) $payload['question']);
        $questionKey = HospitalChatBotQna::questionKey($question);

        if ($this->duplicateQuery->exists($questionKey)) {
            throw ValidationException::withMessages(['question' => '동일한 질문이 이미 등록되어 있습니다.']);
        }

        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $qna = DB::transaction(fn (): HospitalChatBotQna => $this->query->create([
            'category' => trim((string) $payload['category']),
            'question' => $question,
            'question_key' => $questionKey,
            'answer' => trim((string) $payload['answer']),
            'status' => (string) ($payload['status'] ?? HospitalChatBotQna::STATUS_ACTIVE),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'created_by_staff_id' => $staffId,
            'updated_by_staff_id' => $staffId,
        ]));

        return HospitalChatBotQnaForStaffDto::fromModel($qna);
    }
}
