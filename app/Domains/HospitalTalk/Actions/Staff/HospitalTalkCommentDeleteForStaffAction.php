<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalTalk\Models\HospitalTalkCommentMention;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkCommentDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkCommentDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalTalkCommentDeleteForStaffQuery $query,
    ) {}

    public function execute(HospitalTalkComment $comment): array
    {
        Gate::authorize('delete', $comment);

        return DB::transaction(function () use ($comment) {
            $hospitalTalkId = (int) $comment->hospital_talk_id;
            $commentIds = $comment->children()->pluck('id')->prepend($comment->id)->all();

            HospitalTalkCommentMention::query()
                ->whereIn('hospital_talk_comment_id', $commentIds)
                ->delete();

            $this->query->softDelete($comment);
            $comment->refresh();
            $this->refreshTalkCommentCount($hospitalTalkId);

            return [
                'deleted_id' => (int) $comment->id,
                'deleted_at' => optional($comment->deleted_at)?->toISOString(),
            ];
        });
    }

    private function refreshTalkCommentCount(int $hospitalTalkId): void
    {
        $talk = HospitalTalk::query()->find($hospitalTalkId);
        if (! $talk) {
            return;
        }

        $talk->forceFill([
            'comment_count' => (int) $talk->comments()->count(),
        ])->save();
    }
}
