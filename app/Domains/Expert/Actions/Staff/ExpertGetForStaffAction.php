<?php

namespace App\Domains\Expert\Actions\Staff;

use App\Domains\Expert\Dto\Staff\ExpertForStaffDetailDto;
use App\Domains\Expert\Models\Expert;
use Illuminate\Support\Facades\Gate;

final class ExpertGetForStaffAction
{
    public function execute(Expert $expert): array
    {
        Gate::authorize('view', $expert);

        $expert->load([
            'profileImage',
            'educationCertificateImages',
            'etcCertificateImages',
        ]);

        return [
            'expert' => ExpertForStaffDetailDto::fromModel($expert)->toArray(),
        ];
    }
}
