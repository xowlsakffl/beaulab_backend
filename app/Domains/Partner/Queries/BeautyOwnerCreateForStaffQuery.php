<?php

namespace App\Domains\Partner\Queries;

use App\Domains\Beauty\Models\AccountBeauty;

final class BeautyOwnerCreateForStaffQuery
{
    public function create(array $data): AccountBeauty
    {
        return AccountBeauty::create($data);
    }
}
