<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class HospitalEventPreviewContextForStaffAction
{
    public function execute(array $filters): array
    {
        Gate::authorize('preview', HospitalEvent::class);

        $hospital = Hospital::query()->select(['id', 'name'])->findOrFail($filters['hospital_id']);
        $doctorIds = array_map('intval', $filters['doctor_ids'] ?? []);
        $doctors = HospitalDoctor::query()
            ->where('hospital_id', $hospital->id)
            ->whereIn('id', $doctorIds)
            ->select(['id', 'name', 'position', 'educations', 'careers', 'etc_contents'])
            ->with('profileImage')
            ->get();

        if ($doctors->count() !== count($doctorIds)) {
            throw ValidationException::withMessages(['doctor_ids' => ['선택한 병의원의 의료진만 미리볼 수 있습니다.']]);
        }

        return [
            'hospital_id' => (int) $hospital->id,
            'features' => $hospital->features()->active()->orderBy('sort_order')->get()
                ->map(fn ($feature): array => [
                    'id' => (int) $feature->id,
                    'code' => $feature->code,
                    'name' => $feature->name,
                ])->values()->all(),
            'doctors' => $doctors->map(fn (HospitalDoctor $doctor): array => [
                'id' => (int) $doctor->id,
                'name' => $doctor->name,
                'position' => $doctor->position,
                'educations' => $doctor->educations ?? [],
                'careers' => $doctor->careers ?? [],
                'etc_contents' => $doctor->etc_contents ?? [],
                'profile_image' => $doctor->profileImage ? [
                    'id' => (int) $doctor->profileImage->id,
                    'path' => $doctor->profileImage->publicPath(),
                    'metadata' => $doctor->profileImage->publicMetadata(),
                ] : null,
            ])->values()->all(),
        ];
    }
}
