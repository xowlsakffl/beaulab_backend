<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaUpdateForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaUpdateForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaUpdateForStaffQuery $query, private readonly HospitalChatBotQnaTreeQuery $tree) {}

    public function execute(HospitalChatBotQna $qna, array $payload): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_QNA_SHOW, P::BEAULAB_CHATBOT_QNA_UPDATE);

        try {
            return DB::transaction(function () use ($qna, $payload): array {
                $nodes = $this->tree->all(true);
                $locked = $nodes->firstWhere('id', $qna->id);
                abort_if($locked === null, 404);
                $parentId = array_key_exists('parent_id', $payload) ? ($payload['parent_id'] !== null ? (int) $payload['parent_id'] : null) : $locked->parent_id;
                $depth = $this->tree->depth($nodes, $parentId, (int) $locked->id);
                $descendants = $this->tree->descendants($nodes, (int) $locked->id);
                $delta = $depth - $locked->depth;
                if ($descendants->isNotEmpty() && $descendants->max('depth') + $delta > HospitalChatBotQnaTreeQuery::MAX_DEPTH) {
                    throw ValidationException::withMessages(['parent_id' => '하위 항목까지 포함하면 3단계를 초과합니다.']);
                }
                $question = trim((string) ($payload['question'] ?? $locked->question));
                $oldParentId = $locked->parent_id;
                $position = $payload['position'] ?? null;
                unset($payload['position']);
                $values = [...$payload, 'parent_id' => $parentId, 'depth' => $depth, 'question' => $question,
                    'question_key' => HospitalChatBotQnaTreeQuery::questionKey($question, $parentId), 'updated_by_staff_id' => auth()->id()];
                if (array_key_exists('answer', $values)) {
                    $values['answer'] = trim((string) $values['answer']);
                }
                $updated = $this->query->update($locked, $values);
                foreach ($descendants as $child) {
                    if ($delta !== 0) {
                        $child->forceFill(['depth' => $child->depth + $delta])->save();
                    }
                }

                if ($position !== null) {
                    // 전체 트리 잠금 안에서 원래 위치와 새 위치의 형제 순서를 함께 갱신한다.
                    $siblings = $nodes->filter(fn ($node) => $node->id !== $locked->id && $node->parent_id === $parentId)
                        ->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
                    $siblings->splice(min((int) $position, $siblings->count()), 0, [$updated]);
                    foreach ($siblings as $index => $sibling) {
                        $sibling->forceFill(['sort_order' => $index, 'updated_by_staff_id' => auth()->id()])->save();
                    }
                    if ($oldParentId !== $parentId) {
                        foreach ($nodes->filter(fn ($node) => $node->id !== $locked->id && $node->parent_id === $oldParentId)
                            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values() as $index => $sibling) {
                            $sibling->forceFill(['sort_order' => $index, 'updated_by_staff_id' => auth()->id()])->save();
                        }
                    }
                }

                return HospitalChatBotQnaForStaffDto::fromModel($updated);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['question' => '같은 상위 항목에 동일한 질문이 있습니다.']);
        }
    }
}
