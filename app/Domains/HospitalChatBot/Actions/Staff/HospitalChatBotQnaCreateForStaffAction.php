<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaCreateForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDuplicateForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaFolderForStaffQuery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaCreateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotQnaCreateForStaffQuery $query,
        private readonly HospitalChatBotQnaFolderForStaffQuery $folders,
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

        try {
            $qna = DB::transaction(fn (): HospitalChatBotQna => $this->query->create([
                'folder_id' => (int) $payload['folder_id'],
                // category는 폴더 이름을 따라간다. 목록 필터와 기존 조회가 아직 이 값을 쓴다.
                'category' => $this->folders->find((int) $payload['folder_id'])->name,
                'question' => $question,
                'question_key' => $questionKey,
                'answer' => trim((string) $payload['answer']),
                'status' => (string) ($payload['status'] ?? HospitalChatBotQna::STATUS_ACTIVE),
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
                'created_by_staff_id' => $staffId,
                'updated_by_staff_id' => $staffId,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['question' => '동일한 질문이 이미 등록되어 있습니다.']);
        }

        // DTO는 관계를 지연 로드하지 않는다. 폴더 이름을 실으려면 여기서 붙여야 한다.
        return HospitalChatBotQnaForStaffDto::fromModel($qna->loadMissing('folder:id,parent_id,name'));
    }
}
