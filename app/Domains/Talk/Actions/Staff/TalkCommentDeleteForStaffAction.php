<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;
use App\Domains\Talk\Queries\Staff\TalkCommentDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkCommentDeleteForStaffAction
{
    public function __construct(
        private readonly TalkCommentDeleteForStaffQuery $query,
    ) {}

    public function execute(TalkComment $comment): array
    {
        Gate::authorize('delete', $comment);

        return DB::transaction(function () use ($comment) {
            $talkId = (int) $comment->talk_id;
            $commentIds = $comment->children()->pluck('id')->prepend($comment->id)->all();

            TalkCommentMention::query()
                ->whereIn('talk_comment_id', $commentIds)
                ->delete();

            $this->query->softDelete($comment);
            $comment->refresh();
            $this->refreshTalkCommentCount($talkId);

            return [
                'deleted_id' => (int) $comment->id,
                'deleted_at' => optional($comment->deleted_at)?->toISOString(),
            ];
        });
    }

    private function refreshTalkCommentCount(int $talkId): void
    {
        $talk = Talk::query()->find($talkId);
        if (! $talk) {
            return;
        }

        $talk->forceFill([
            'comment_count' => (int) $talk->comments()->count(),
        ])->save();
    }
}
