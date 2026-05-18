<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Domains\HospitalEvaluation\Dto\Staff\HospitalEvaluationForStaffDetailDto;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationGetForStaffAction
{
    public function execute(HospitalEvaluation $evaluation, array $filters = []): array
    {
        Gate::authorize('view', $evaluation);

        $evaluation->load([
            'author',
            'hospital.businessRegistration',
            'doctor',
            'categories',
            'images',
            'receiptImages',
            'contentReportState',
        ]);

        return [
            'evaluation' => HospitalEvaluationForStaffDetailDto::fromModel($evaluation)->toArray(),
        ];
    }
}
