<?php

namespace App\Domains\HospitalTalk\Dto\Staff;

use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalTalk\Models\HospitalTalkCommentMention;
use Illuminate\Support\Collection;

final readonly class HospitalTalkCommentForStaffDetailDto
{
    public function __construct(public array $comment) {}

    public static function fromModel(
        HospitalTalkComment $comment,
        bool $includeChildren = false,
        bool $includeMentions = true,
    ): self
    {
        $data = [
            'id' => (int) $comment->id,
            'hospital_talk_id' => (int) $comment->hospital_talk_id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'is_reply' => $comment->isReply(),
            'author_id' => $comment->author_id ? (int) $comment->author_id : null,
            'author' => $comment->relationLoaded('author') && $comment->author
                ? [
                    'id' => (int) $comment->author->id,
                    'name' => (string) $comment->author->name,
                    'email' => (string) $comment->author->email,
                ]
                : null,
            'talk' => $comment->relationLoaded('talk') && $comment->talk
                ? [
                    'id' => (int) $comment->talk->id,
                    'title' => (string) $comment->talk->title,
                ]
                : null,
            'content' => (string) $comment->content,
            'status' => (string) $comment->status,
            'is_visible' => (bool) $comment->is_visible,
            'author_ip' => $comment->author_ip,
            'like_count' => (int) $comment->like_count,
            'mention_count' => (int) ($comment->mentions_count ?? $comment->mentions()->count()),
            'admin_notes' => self::resolveAdminNotes($comment)
                ->map(fn (AdminNote $note): array => [
                    'id' => (int) $note->id,
                    'note' => (string) $note->note,
                    'is_internal' => (bool) $note->is_internal,
                    'created_by_staff_id' => $note->created_by_staff_id ? (int) $note->created_by_staff_id : null,
                    'created_by_staff_name' => $note->relationLoaded('creator') && $note->creator
                        ? (string) $note->creator->name
                        : null,
                    'created_at' => $note->created_at?->toISOString(),
                    'updated_at' => $note->updated_at?->toISOString(),
                ])
                ->values()
                ->all(),
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
            'deleted_at' => $comment->deleted_at?->toISOString(),
        ];

        if ($includeMentions) {
            $data['mentions'] = self::resolveMentions($comment)
                ->map(fn (HospitalTalkCommentMention $mention): array => [
                    'id' => (int) $mention->id,
                    'mentioned_user_id' => (int) $mention->mentioned_user_id,
                    'mentioned_user_name' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                        ? (string) $mention->mentionedUser->name
                        : null,
                    'mentioned_by_user_id' => $mention->mentioned_by_user_id ? (int) $mention->mentioned_by_user_id : null,
                    'mention_text' => $mention->mention_text,
                    'start_offset' => $mention->start_offset,
                    'end_offset' => $mention->end_offset,
                ])
                ->values()
                ->all();
        }

        if ($includeChildren) {
            $data['children'] = self::resolveChildren($comment)
                ->map(fn (HospitalTalkComment $child): array => [
                    'id' => (int) $child->id,
                    'parent_id' => $child->parent_id ? (int) $child->parent_id : null,
                    'is_reply' => $child->isReply(),
                    'author_id' => $child->author_id ? (int) $child->author_id : null,
                    'author_name' => $child->relationLoaded('author') && $child->author
                        ? (string) $child->author->name
                        : null,
                    'content' => (string) $child->content,
                    'status' => (string) $child->status,
                    'is_visible' => (bool) $child->is_visible,
                    'like_count' => (int) $child->like_count,
                    'created_at' => $child->created_at?->toISOString(),
                    'updated_at' => $child->updated_at?->toISOString(),
                    'deleted_at' => $child->deleted_at?->toISOString(),
                ])
                ->values()
                ->all();
        }

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->comment;
    }

    /**
     * @return Collection<int, AdminNote>
     */
    private static function resolveAdminNotes(HospitalTalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('adminNotes')) {
            return collect();
        }

        return $comment->adminNotes;
    }

    /**
     * @return Collection<int, HospitalTalkComment>
     */
    private static function resolveChildren(HospitalTalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('children')) {
            return collect();
        }

        return $comment->children;
    }

    /**
     * @return Collection<int, HospitalTalkCommentMention>
     */
    private static function resolveMentions(HospitalTalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('mentions')) {
            return collect();
        }

        return $comment->mentions;
    }
}
