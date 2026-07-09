<?php

namespace App\Domains\HospitalVideo\Actions\Hospital;

use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoUpdateHistoryRecordAction;
use App\Domains\HospitalVideo\Dto\Hospital\HospitalVideoForHospitalDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Hospital\HospitalVideoStatusUpdateForHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoStatusUpdateForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoStatusUpdateForHospitalQuery $query,
        private readonly HospitalVideoUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalVideo $video, array $payload): array
    {
        Gate::authorize('update', $video);

        $beforeStatus = (string) $video->hospital_status;

        $video = DB::transaction(function () use ($video, $payload, $beforeStatus) {
            $updated = $this->query->updateHospitalStatus($video, (string) $payload['hospital_status']);
            $this->historyRecordAction->recordHospitalStatusUpdated(
                $updated,
                $beforeStatus,
                (string) $updated->hospital_status,
            );

            return $updated->load([
                'hospital',
                'hospital.businessRegistration',
                'doctor',
                'thumbnailMedia',
                'categories',
                'hashtags',
            ]);
        });

        return [
            'video' => HospitalVideoForHospitalDetailDto::fromModel($video)->toArray(),
        ];
    }
}
