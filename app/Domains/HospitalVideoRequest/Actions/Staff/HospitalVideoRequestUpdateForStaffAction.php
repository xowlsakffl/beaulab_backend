<?php

namespace App\Domains\HospitalVideoRequest\Actions\Staff;

use App\Domains\HospitalVideoRequest\Dto\Staff\HospitalVideoRequestForStaffDetailDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Staff\HospitalVideoRequestUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestUpdateForStaffAction
{
    public function __construct(private readonly HospitalVideoRequestUpdateForStaffQuery $query) {}

    public function execute(HospitalVideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        $videoRequest = DB::transaction(fn () => $this->query->update($videoRequest, $payload));

        return [
            'video_request' => HospitalVideoRequestForStaffDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
