<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Illuminate\Support\Facades\Gate;

final class BeautyExpertGetForStaffAction
{
    public function execute(BeautyExpert $expert): array
    {
        Gate::authorize('view', $expert);

        $expert->load([
            'profileImage',
            'educationCertificateImages',
            'etcCertificateImages',
            'categories',
        ]);

        return [
            'expert' => BeautyExpertForStaffDetailDto::fromModel($expert)->toArray(),
        ];
    }
}
