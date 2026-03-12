<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;

final class NoticeDeleteForStaffQuery
{
    public function delete(Notice $notice): void
    {
        $notice->delete();
    }
}
