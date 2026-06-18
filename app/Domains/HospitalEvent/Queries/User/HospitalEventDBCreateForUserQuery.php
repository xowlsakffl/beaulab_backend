<?php

namespace App\Domains\HospitalEvent\Queries\User;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;

final class HospitalEventDBCreateForUserQuery
{
    public function create(array $values): HospitalEventDB
    {
        return HospitalEventDB::query()->create($values);
    }

    public function existsDuplicate(HospitalEvent $event, string $name, string $phone): bool
    {
        return HospitalEventDB::query()
            ->where('hospital_event_id', (int) $event->id)
            ->where('phone_normalized', HospitalEventDB::normalizePhone($phone))
            ->where('name', trim($name))
            ->exists();
    }

    public function doctorBelongsToEvent(HospitalEvent $event, int $doctorId): bool
    {
        return $event->doctors()
            ->where('hospital_doctors.id', $doctorId)
            ->exists();
    }
}
