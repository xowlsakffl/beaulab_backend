<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Dto\Staff\TalkCommentForStaffDetailDto;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Facades\Gate;

final class TalkCommentGetForStaffAction
{
    public function execute(TalkComment $comment, array $filters = []): array
    {
        Gate::authorize('view', $comment);

        $include = $filters['include'] ?? [];
        $includeChildren = is_array($include) && in_array('children', $include, true);
        $includeMentions = is_array($include) && in_array('mentions', $include, true);

        $relations = [
            'author',
            'talk',
            'adminNotes.creator',
        ];

        if ($includeChildren) {
            $relations[] = 'children.author';
        }

        if ($includeMentions) {
            $relations[] = 'mentions.mentionedUser';
        }

        $comment->load($relations)->loadCount('mentions');

        return [
            'comment' => TalkCommentForStaffDetailDto::fromModel($comment, $includeChildren, $includeMentions)->toArray(),
        ];
    }
}
