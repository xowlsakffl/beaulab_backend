<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use Illuminate\Support\Facades\Gate;

final class BeautyGetForStaffAction
{
    /**
     * @param array<int, string> $include
     * @return array{beauty: array}
     */
    public function execute(Beauty $beauty, array $include = []): array
    {
        Gate::authorize('view', $beauty);

        $relations = [];

        if (in_array('business_registration', $include, true)) {
            $relations[] = 'businessRegistration.certificateMedia';
        }

        if (in_array('account_partners', $include, true)) {
            $relations[] = 'partners';
        }

        if ($relations !== []) {
            $beauty->load($relations);
        }

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel($beauty, $include)->toArray(),
        ];
    }
}
