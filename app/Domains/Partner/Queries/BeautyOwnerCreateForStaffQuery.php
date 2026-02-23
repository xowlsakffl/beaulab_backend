<?php

namespace App\Domains\Partner\Queries;

use App\Domains\Partner\Models\AccountPartner;

final class BeautyOwnerCreateForStaffQuery
{
    public function create(array $data): AccountPartner
    {
        return AccountPartner::create($data);
    }
}
