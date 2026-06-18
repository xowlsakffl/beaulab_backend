<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\User;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;

final class HospitalEventRealModelDBCreateForUserQuery
{
    public function create(array $values): HospitalEventRealModelDB
    {
        return HospitalEventRealModelDB::query()->create($values);
    }
}
