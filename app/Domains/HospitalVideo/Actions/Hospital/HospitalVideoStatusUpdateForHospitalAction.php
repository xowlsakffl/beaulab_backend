<?php

namespace App\Domains\HospitalVideo\Actions\Hospital;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalVideo\Dto\Hospital\HospitalVideoForHospitalDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Hospital\HospitalVideoStatusUpdateForHospitalQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoStatusUpdateForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoStatusUpdateForHospitalQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(HospitalVideo $video, array $payload): array
    {
        Gate::authorize('update', $video);

        $actor = auth()->user();

        $video = DB::transaction(function () use ($video, $payload, $actor) {
            $beforeStatus = (string) $video->hospital_status;
            $updated = $this->query->update($video, (string) $payload['hospital_status']);
            $afterStatus = (string) $updated->hospital_status;

            if ($beforeStatus !== $afterStatus) {
                $this->historyCreateAction->execute(
                    target: $updated,
                    action: OperationHistory::ACTION_STATE_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: null,
                    metadata: ['source' => 'hospital.hospital_video.hospital_status'],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'hospital_status',
                        label: '공개여부 변경',
                        before: $beforeStatus,
                        after: $afterStatus,
                        beforeDisplay: HospitalVideo::hospitalStatusLabel($beforeStatus),
                        afterDisplay: HospitalVideo::hospitalStatusLabel($afterStatus),
                    ),
                );
            }

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
