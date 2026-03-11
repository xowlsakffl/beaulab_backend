<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Actions\AdminNote\AdminNoteCreateAction;
use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkCommentForStaffDetailDto;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkCommentCreateForStaffQuery;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkCommentMentionSyncForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkCommentCreateForStaffAction
{
    public function __construct(
        private readonly HospitalTalkCommentCreateForStaffQuery $query,
        private readonly AdminNoteCreateAction $adminNoteCreateAction,
        private readonly HospitalTalkCommentMentionSyncForStaffQuery $mentionSyncQuery,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalTalkComment::class);

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
            'comment' => HospitalTalkCommentForStaffDetailDto::fromModel($comment)->toArray(),
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

        $parent = HospitalTalkComment::query()->find($parentId);
        if (! $parent || (int) $parent->hospital_talk_id !== $hospitalTalkId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '부모 댓글과 게시글이 일치하지 않습니다.');
        }
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

    private function createAdminNoteIfRequested(HospitalTalkComment $comment, array $payload): void
    {
        $note = trim((string) ($payload['admin_note'] ?? ''));
        if ($note === '') {
            return;
        }

        $actor = auth()->user();
        $createdByStaffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $this->adminNoteCreateAction->execute($comment, $note, $createdByStaffId, true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertMentionsAllowed(?int $parentId, array $payload): void
    {
        $mentions = is_array($payload['mentions'] ?? null) ? $payload['mentions'] : [];

        if (count($mentions) > 1) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '멘션은 한 명만 지정할 수 있습니다.'
            );
        }

        if ($parentId === null && $mentions !== []) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '멘션은 답글(대댓글)에서만 사용할 수 있습니다.'
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncMentions(HospitalTalkComment $comment, array $payload): void
    {
        $mentions = is_array($payload['mentions'] ?? null) ? $payload['mentions'] : [];
        $mentionedByUserId = $comment->author_id ? (int) $comment->author_id : null;

        $this->mentionSyncQuery->sync($comment, $mentions, $mentionedByUserId);
    }
}
