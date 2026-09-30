<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDuplicateForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaFolderForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaUpdateForStaffQuery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotQnaUpdateForStaffQuery $query,
        private readonly HospitalChatBotQnaDuplicateForStaffQuery $duplicateQuery,
        private readonly HospitalChatBotQnaFolderForStaffQuery $folders,
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

        return HospitalChatBotQnaForStaffDto::fromModel($updated->loadMissing('folder:id,parent_id,name'));
    }

    private function normalizePayload(array $payload, HospitalChatBotQna $qna, ?int $staffId): array
    {
        $normalized = ['updated_by_staff_id' => $staffId];

        if (array_key_exists('answer', $payload)) {
            $normalized['answer'] = trim((string) $payload['answer']);
        }

        if (array_key_exists('folder_id', $payload)) {
            $folder = $this->folders->find((int) $payload['folder_id']);
            $normalized['folder_id'] = (int) $folder->id;
            // category는 폴더 이름을 따라간다. 목록 필터와 기존 조회가 아직 이 값을 쓴다.
            $normalized['category'] = (string) $folder->name;
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
