<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;

final class BeautyDeleteForStaffQuery
{
    /**
     * 병원 삭제 soft delete
     */
    public function softDelete(Beauty $beauty): void
    {
        $beauty->delete();
    }
}
