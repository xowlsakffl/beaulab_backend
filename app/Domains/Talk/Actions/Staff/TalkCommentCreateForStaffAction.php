<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\Talk\Dto\Staff\TalkCommentForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\Staff\TalkCommentCreateForStaffQuery;
use App\Domains\Talk\Queries\Staff\TalkCommentMentionSyncForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkCommentCreateForStaffAction
{
    public function __construct(
        private readonly TalkCommentCreateForStaffQuery $query,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
        private readonly TalkCommentMentionSyncForStaffQuery $mentionSyncQuery,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', TalkComment::class);

        $normalized = $this->normalizePayload($payload);
        $this->assertParentBelongsToTalk($normalized['hospital_talk_id'], $normalized['parent_id'] ?? null);
        $this->assertMentionsAllowed($normalized['parent_id'] ?? null, $normalized);

        $comment = DB::transaction(function () use ($normalized) {
            $comment = $this->query->create($normalized);

            $this->syncMentions($comment, $normalized);
            $this->refreshTalkCommentCount((int) $comment->hospital_talk_id);
            $this->createAdminNoteIfRequested($comment, $normalized);

            return $comment->fresh([
                'author',
                'talk',
                'mentions.mentionedUser',
                'adminNotes.creator',
            ])->loadCount('mentions');
        });

        return [
            'comment' => TalkCommentForStaffDetailDto::fromModel($comment)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $payload['author_ip'] = request()->ip();

        return $payload;
    }

    private function assertParentBelongsToTalk(int $hospitalTalkId, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = TalkComment::query()->find($parentId);
        if (! $parent || (int) $parent->hospital_talk_id !== $hospitalTalkId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '부모 댓글과 게시글이 일치하지 않습니다.');
        }
    }

    private function refreshTalkCommentCount(int $hospitalTalkId): void
    {
        $talk = Talk::query()->find($hospitalTalkId);
        if (! $talk) {
            return;
        }

        $talk->forceFill([
            'comment_count' => (int) $talk->comments()->count(),
        ])->save();
    }

    private function createAdminNoteIfRequested(TalkComment $comment, array $payload): void
    {
        $note = trim((string) ($payload['admin_note'] ?? ''));
        if ($note === '') {
            return;
        }

        $actor = auth()->user();

        $this->adminNoteCreateAction->execute(
            $comment,
            $note,
            $actor instanceof AccountStaff ? $actor : null,
            true,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertMentionsAllowed(?int $parentId, array $payload): void
    {
        $mention = $this->resolveMention($payload);

        if ($parentId === null && $mention !== null) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '멘션은 답글에서만 사용할 수 있습니다.'
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncMentions(TalkComment $comment, array $payload): void
    {
        $mention = $this->resolveMention($payload);
        $mentionedByUserId = $comment->author_id ? (int) $comment->author_id : null;

        $this->mentionSyncQuery->sync($comment, $mention, $mentionedByUserId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{user_id:int, mention_text?:string|null}|null
     */
    private function resolveMention(array $payload): ?array
    {
        $mention = $payload['mentions'] ?? null;
        if (! is_array($mention) || ! array_key_exists('user_id', $mention)) {
            return null;
        }

        return $mention;
    }
}
