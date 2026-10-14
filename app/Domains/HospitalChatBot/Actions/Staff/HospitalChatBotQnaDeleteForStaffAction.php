<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaDeleteForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HospitalChatBotQnaDeleteForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaDeleteForStaffQuery $query, private readonly HospitalChatBotQnaTreeQuery $tree) {}

    public function execute(HospitalChatBotQna $qna): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_QNA_SHOW, P::BEAULAB_CHATBOT_QNA_DELETE);

        DB::transaction(function () use ($qna): void {
            $nodes = $this->tree->all(true);
            $locked = $nodes->firstWhere('id', $qna->id);
            abort_if($locked === null, 404);
            if ($nodes->contains('parent_id', $qna->id)) {
                throw ValidationException::withMessages(['qna' => '하위 항목을 먼저 이동하거나 삭제해 주세요.']);
            }
            $this->query->delete($locked);
        }, 3);

        return ['deleted' => true, 'id' => (int) $qna->id];
    }
}
