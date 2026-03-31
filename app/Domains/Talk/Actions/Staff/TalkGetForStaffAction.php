<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Facades\Gate;

final class TalkGetForStaffAction
{
    public function execute(Talk $talk, array $filters = []): array
    {
        Gate::authorize('view', $talk);

        $include = $filters['include'] ?? [];
        $includeComments = is_array($include) && in_array('comments', $include, true);

        $relations = [
            'author',
            'categories',
            'images',
            'adminNotes.creator',
        ];

        if ($includeComments) {
            $relations[] = 'comments.author';
        }

        $talk->load($relations);

        return [
            'talk' => TalkForStaffDetailDto::fromModel($talk, $includeComments)->toArray(),
        ];
    }
}
