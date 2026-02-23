<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use Illuminate\Support\Facades\Gate;

final class BeautyGetForStaffAction
{
    /**
     * @return array{beauty: array}
     */
    public function execute(Beauty $beauty): array
    {
        Gate::authorize('view', $beauty);

        $beauty->load('businessRegistration.certificateMedia');

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel($beauty)->toArray(),
        ];
    }
}
