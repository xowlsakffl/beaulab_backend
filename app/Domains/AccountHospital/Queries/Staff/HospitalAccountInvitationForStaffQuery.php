<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries\Staff;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class HospitalAccountInvitationForStaffQuery
{
    public function findSource(string $sourceType, int $sourceId): Model
    {
        return match ($sourceType) {
            HospitalAccountInvitation::SOURCE_HOSPITAL => Hospital::query()->findOrFail($sourceId),
            HospitalAccountInvitation::SOURCE_HOSPITAL_ENTRY => HospitalEntry::query()->findOrFail($sourceId),
        };
    }

    public function lockSource(string $sourceType, int $sourceId): Model
    {
        return match ($sourceType) {
            HospitalAccountInvitation::SOURCE_HOSPITAL => Hospital::query()
                ->with(['accountHospital', 'businessRegistration'])
                ->lockForUpdate()
                ->findOrFail($sourceId),
            HospitalAccountInvitation::SOURCE_HOSPITAL_ENTRY => HospitalEntry::query()
                ->with(['businessRegistrationFile', 'hospital'])
                ->lockForUpdate()
                ->findOrFail($sourceId),
        };
    }

    public function paginate(string $sourceType, int $sourceId, int $perPage): LengthAwarePaginator
    {
        return $this->sourceQuery($sourceType, $sourceId)
            ->with('createdByStaff:id,name')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function revokeActive(string $sourceType, int $sourceId): void
    {
        $this->sourceQuery($sourceType, $sourceId)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now(), 'updated_at' => now()]);
    }

    public function latestActive(string $sourceType, int $sourceId): ?HospitalAccountInvitation
    {
        return $this->sourceQuery($sourceType, $sourceId)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function recoverPreviousAfterFailure(
        string $sourceType,
        int $sourceId,
        HospitalAccountInvitation $failedInvitation,
        ?int $previousInvitationId,
    ): void {
        $failedInvitation->forceFill(['revoked_at' => now()])->save();

        if ($previousInvitationId === null) {
            return;
        }

        $hasNewerActiveInvitation = $this->sourceQuery($sourceType, $sourceId)
            ->whereKeyNot($failedInvitation->getKey())
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($hasNewerActiveInvitation) {
            return;
        }

        HospitalAccountInvitation::query()
            ->whereKey($previousInvitationId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => null, 'updated_at' => now()]);
    }

    public function create(array $attributes): HospitalAccountInvitation
    {
        return HospitalAccountInvitation::query()->create($attributes);
    }

    private function sourceQuery(string $sourceType, int $sourceId): Builder
    {
        return HospitalAccountInvitation::query()
            ->where('source_type', $sourceType)
            ->when(
                $sourceType === HospitalAccountInvitation::SOURCE_HOSPITAL,
                static fn ($query) => $query->where('hospital_id', $sourceId),
                static fn ($query) => $query->where('hospital_entry_id', $sourceId),
            );
    }
}
