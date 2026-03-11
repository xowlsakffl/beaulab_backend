<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkForStaffDetailDto;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkGetForStaffAction
{
    public function execute(HospitalTalk $talk, array $filters = []): array
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
            'talk' => HospitalTalkForStaffDetailDto::fromModel($talk, $includeComments)->toArray(),
        ];
    }
}
