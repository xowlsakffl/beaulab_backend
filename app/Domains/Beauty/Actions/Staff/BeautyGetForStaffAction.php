<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Expert\Models\BeautyExpert;
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

        $relations = ['logoMedia', 'galleryMedia'];

        if (in_array('business_registration', $include, true)) {
            $relations[] = 'businessRegistration.certificateMedia';
        }

        if (in_array('account_beauties', $include, true)) {
            $relations[] = 'accountBeauties.roles';
        }

        if (in_array('experts', $include, true)) {
            Gate::authorize('viewAny', BeautyExpert::class);
            $relations[] = 'experts.profileImage';
        }

        if ($relations !== []) {
            $beauty->load($relations);
        }

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel($beauty, $include)->toArray(),
        ];
    }
}
