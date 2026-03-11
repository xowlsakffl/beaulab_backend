<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkCommentForStaffDetailDto;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkCommentGetForStaffAction
{
    public function execute(HospitalTalkComment $comment, array $filters = []): array
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
            'comment' => HospitalTalkCommentForStaffDetailDto::fromModel($comment, $includeChildren, $includeMentions)->toArray(),
        ];
    }
}
