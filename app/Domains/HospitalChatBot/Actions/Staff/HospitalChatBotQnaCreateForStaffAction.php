<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaCreateForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaCreateForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaCreateForStaffQuery $query, private readonly HospitalChatBotQnaTreeQuery $tree) {}

    public function execute(array $payload): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_QNA_SHOW, P::BEAULAB_CHATBOT_QNA_CREATE);

        try {
            return DB::transaction(function () use ($payload): array {
                $nodes = $this->tree->all(true);
                $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
                $question = trim($payload['question']);
                $qna = $this->query->create([
                    'parent_id' => $parentId,
                    'depth' => $this->tree->depth($nodes, $parentId),
                    'category' => '',
                    'question' => $question,
                    'question_key' => HospitalChatBotQnaTreeQuery::questionKey($question, $parentId),
                    'answer' => trim((string) ($payload['answer'] ?? '')),
                    'status' => $payload['status'] ?? 'ACTIVE',
                    'sort_order' => $payload['sort_order'] ?? 0,
                    'created_by_staff_id' => auth()->id(),
                    'updated_by_staff_id' => auth()->id(),
                ]);

                return HospitalChatBotQnaForStaffDto::fromModel($qna);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['question' => '같은 상위 항목에 동일한 질문이 있습니다.']);
        }
    }
}
