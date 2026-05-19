<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Facades\Gate;

final class TalkCommentsForStaffAction
{
    public function execute(Talk $talk, array $filters = []): array
    {
        Gate::authorize('view', $talk);

        $comments = $talk->comments()
            ->with(['author', 'contentReportState', 'operationHistories.actor', 'mentions.mentionedUser'])
            ->paginate(
                perPage: (int) ($filters['comments_per_page'] ?? 10),
                pageName: 'comments_page',
                page: (int) ($filters['comments_page'] ?? 1),
            );

        return PaginatedResponse::fromPaginator(
            $comments,
            fn ($comment): array => TalkForStaffDetailDto::comment($comment),
        );
    }
}
