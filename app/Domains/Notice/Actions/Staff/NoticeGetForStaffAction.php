<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Notice\Dto\Staff\NoticeForStaffDetailDto;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Gate;

final class NoticeGetForStaffAction
{
    public function execute(Notice $notice): array
    {
        Gate::authorize('view', $notice);

        $loaded = $notice->load([
            'attachments',
            'popupImage',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return [
            'notice' => NoticeForStaffDetailDto::fromModel($loaded)->toArray(),
        ];
    }
}
